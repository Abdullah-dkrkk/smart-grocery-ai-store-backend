// Queue consumer - processes image generation jobs in background
// This runs on Cloudflare Workers infrastructure, NOT on your machine
// It continues running 24/7 even if you close VS Code or terminal

export default {
  async queue(batch, env) {
    for (const message of batch.messages) {
      try {
        await processImageJob(message.body, env);
        message.ack();
      } catch (error) {
        console.error('Job failed:', message.body, error);
        message.retry();
      }
    }
  },
};

async function processImageJob(job, env) {
  const { productId, productName, slug, variationType } = job;

  const variationConfigs = {
    packaged_front: { suffix: 'professional product photography, front view, packaged in retail packaging, studio lighting, white background', seedOffset: 100 },
    packaged_angle: { suffix: 'professional product photography, 45 degree angle view, packaged in retail packaging, soft lighting, clean background', seedOffset: 200 },
    lifestyle: { suffix: 'lifestyle product photography, placed in modern kitchen setting, natural lighting, home environment', seedOffset: 300 },
    closeup: { suffix: 'extreme close-up product photography, showing texture and details, macro shot, professional lighting', seedOffset: 400 },
    ingredients: { suffix: 'product photography with raw ingredients displayed around it, flat lay composition, natural lighting', seedOffset: 500 },
    prepared: { suffix: 'professional food photography, product prepared and ready to consume, beautiful plating', seedOffset: 600 },
    white_background: { suffix: 'product photography, isolated on pure white background, e-commerce style, clean studio shot', seedOffset: 700 },
  };

  const config = variationConfigs[variationType];
  if (!config) {
    throw new Error(`Unknown variation type: ${variationType}`);
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
  const filename = `${slug}-${variationType}.jpg`;

  await env.PRODUCT_IMAGE_BUCKET.put(`variations/${filename}`, imageBuffer, {
    httpMetadata: { contentType: 'image/jpeg' },
    customMetadata: {
      productId: String(productId),
      variationType,
      productName,
    },
  });

  const publicUrl = `${env.R2_PUBLIC_URL}/variations/${filename}`;

  await fetch(`${env.BACKEND_API_URL}/api/internal/product-images`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${env.API_SECRET_KEY}`,
    },
    body: JSON.stringify({
      productId,
      imageUrl: publicUrl,
      variationType,
      isPrimary: variationType === 'packaged_front',
    }),
  });

  console.log(`Generated ${variationType} for ${productName} (ID: ${productId})`);
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
