const VARIATION_TYPES = [
  { type: 'packaged_front', suffix: 'professional product photography, front view, packaged in retail packaging, studio lighting, white background', seedOffset: 100 },
  { type: 'packaged_angle', suffix: 'professional product photography, 45 degree angle view, packaged in retail packaging, soft lighting, clean background', seedOffset: 200 },
  { type: 'lifestyle', suffix: 'lifestyle product photography, placed in modern kitchen setting, natural lighting, home environment', seedOffset: 300 },
  { type: 'closeup', suffix: 'extreme close-up product photography, showing texture and details, macro shot, professional lighting', seedOffset: 400 },
  { type: 'ingredients', suffix: 'product photography with raw ingredients displayed around it, flat lay composition, natural lighting', seedOffset: 500 },
  { type: 'prepared', suffix: 'professional food photography, product prepared and ready to consume, beautiful plating', seedOffset: 600 },
  { type: 'white_background', suffix: 'product photography, isolated on pure white background, e-commerce style, clean studio shot', seedOffset: 700 },
];

export default {
  async fetch(request, env, ctx) {
    const url = new URL(request.url);

    if (request.method === 'POST' && url.pathname === '/api/generate') {
      return handleGeneration(request, env, ctx);
    }

    if (request.method === 'GET' && url.pathname === '/api/status') {
      return handleStatus(env);
    }

    if (request.method === 'GET' && url.pathname === '/api/trigger-batch') {
      return handleBatchTrigger(env, ctx);
    }

    return new Response(JSON.stringify({
      success: true,
      message: 'Product Image Generator Worker',
      endpoints: {
        generate: 'POST /api/generate',
        status: 'GET /api/status',
        triggerBatch: 'GET /api/trigger-batch',
      },
    }), {
      headers: { 'Content-Type': 'application/json' },
    });
  },
};

async function handleGeneration(request, env, ctx) {
  try {
    const body = await request.json();
    const { productName, slug, variationType } = body;

    if (!productName || !slug || !variationType) {
      return new Response(JSON.stringify({
        success: false,
        message: 'Missing required fields: productName, slug, variationType',
      }), { status: 400, headers: { 'Content-Type': 'application/json' } });
    }

    const config = VARIATION_TYPES.find(v => v.type === variationType);
    if (!config) {
      return new Response(JSON.stringify({
        success: false,
        message: `Invalid variation type. Valid types: ${VARIATION_TYPES.map(v => v.type).join(', ')}`,
      }), { status: 400, headers: { 'Content-Type': 'application/json' } });
    }

    const prompt = encodeURIComponent(`High quality organic food product: ${productName}, ${config.suffix}`);
    const seed = hashCode(`${slug}-${variationType}`) % 10000;
    const pollinationUrl = `https://image.pollinations.ai/prompt/${prompt}?width=800&height=800&seed=${seed}&model=flux&nologo=true`;

    const response = await fetch(pollinationUrl, {
      cf: { cacheTtl: 86400, cacheEverything: true },
    });

    if (!response.ok) {
      throw new Error(`Pollination.ai returned ${response.status}`);
    }

    const imageBuffer = await response.arrayBuffer();
    const base64Image = btoa(String.fromCharCode(...new Uint8Array(imageBuffer)));

    return new Response(JSON.stringify({
      success: true,
      data: {
        productName,
        slug,
        variationType,
        imageUrl: `data:image/jpeg;base64,${base64Image}`,
        pollinationUrl,
      },
    }), {
      headers: { 'Content-Type': 'application/json' },
    });
  } catch (error) {
    return new Response(JSON.stringify({
      success: false,
      message: error.message,
    }), { status: 500, headers: { 'Content-Type': 'application/json' } });
  }
}

async function handleStatus(env) {
  const queue = await env.PRODUCT_IMAGE_QUEUE.list();

  return new Response(JSON.stringify({
    success: true,
    data: {
      pendingJobs: queue.size || 0,
      availableVariations: VARIATION_TYPES.map(v => v.type),
    },
  }), {
    headers: { 'Content-Type': 'application/json' },
  });
}

async function handleBatchTrigger(env, ctx) {
  const url = new URL(env.BACKEND_API_URL);
  const response = await fetch(`${url.origin}/api/products?per_page=100`);
  const products = await response.json();

  const jobs = [];
  for (const product of products.data) {
    for (const variation of VARIATION_TYPES) {
      jobs.push({
        productId: product.id,
        productName: product.name,
        slug: product.slug,
        variationType: variation.type,
      });
    }
  }

  for (const job of jobs) {
    await env.PRODUCT_IMAGE_QUEUE.send(job);
  }

  return new Response(JSON.stringify({
    success: true,
    message: `Queued ${jobs.length} image generation jobs`,
  }), {
    headers: { 'Content-Type': 'application/json' },
  });
}

function hashCode(str) {
  let hash = 0;
  for (let i = 0; i < str.length; i++) {
    const char = str.charCodeAt(i);
    hash = ((hash << 5) - hash) + char;
    hash |= 0;
  }
  return Math.abs(hash);
}
