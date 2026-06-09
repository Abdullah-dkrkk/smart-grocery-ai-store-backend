<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $nutritionist = User::where('role', 'nutritionist')->first();
        if (!$nutritionist) {
            $nutritionist = User::factory()->create([
                'name' => 'Dr. Sarah Ahmed',
                'email' => 'nutritionist@smartgrocery.com',
                'role' => 'nutritionist',
                'password' => bcrypt('password'),
            ]);
        }

        $foodImages = [
            'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=800&h=500&fit=crop',
            'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=800&h=500&fit=crop',
            'https://images.unsplash.com/photo-1565958011703-44f9829ba187?w=800&h=500&fit=crop',
            'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=800&h=500&fit=crop',
            'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800&h=500&fit=crop',
            'https://images.unsplash.com/photo-1476224203421-9ac39bcb3327?w=800&h=500&fit=crop',
        ];

        $articles = [
            [
                'title' => '10 Essential Superfoods to Supercharge Your Daily Diet',
                'category' => 'Nutrition',
                'content' => "<p>Superfoods are nature's powerhouses — packed with vitamins, minerals, and antioxidants that can transform your health. Here are 10 must-have superfoods you should add to your grocery list today.</p>

<img src=\"https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=800&h=400&fit=crop\" alt=\"Fresh superfoods\" style=\"width:100%; border-radius:12px; margin:20px 0;\" />

<h2>1. Kale</h2>
<p>This leafy green is loaded with vitamins A, C, and K. Add it to smoothies, salads, or bake into crispy kale chips for a healthy snack.</p>

<h2>2. Blueberries</h2>
<p>Rich in antioxidants, blueberries support brain health and reduce inflammation. Toss them in oatmeal, yogurt, or enjoy them fresh.</p>

<h2>3. Quinoa</h2>
<p>A complete protein containing all nine essential amino acids. Use it as a rice substitute or in hearty grain bowls.</p>

<h2>4. Salmon</h2>
<p>Packed with omega-3 fatty acids, salmon is excellent for heart and brain health. Grill, bake, or pan-sear for a delicious meal.</p>

<h2>5. Avocado</h2>
<p>Creamy, delicious, and full of healthy monounsaturated fats. Spread on toast, blend into smoothies, or make guacamole.</p>

<h2>6. Chia Seeds</h2>
<p>These tiny seeds are rich in fiber, protein, and omega-3s. Make chia pudding or sprinkle over cereal.</p>

<h2>7. Sweet Potatoes</h2>
<p>High in beta-carotene and fiber, sweet potatoes are versatile and nutritious. Roast, mash, or make fries.</p>

<h2>8. Greek Yogurt</h2>
<p>Packed with protein and probiotics for gut health. Use as a base for smoothies, parfaits, or savory dips.</p>

<h2>9. Almonds</h2>
<p>A handful of almonds provides healthy fats, vitamin E, and magnesium. Great as a snack or crushed over salads.</p>

<h2>10. Dark Chocolate</h2>
<p>Yes, chocolate! Dark chocolate (70%+ cocoa) is rich in antioxidants. Enjoy a square or two as a guilt-free treat.</p>

<p><strong>Pro Tip:</strong> Mix and match these superfoods throughout your week for maximum variety and nutritional benefits!</p>",
                'tags' => ['superfoods', 'nutrition', 'healthy-eating', 'wellness'],
            ],
            [
                'title' => 'The Ultimate Guide to Meal Prepping for Busy Professionals',
                'category' => 'Meal Prep',
                'content' => "<p>Meal prepping is the secret weapon of busy professionals who want to eat healthy without spending hours in the kitchen every day.</p>

<img src=\"https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=800&h=400&fit=crop\" alt=\"Meal prep containers\" style=\"width:100%; border-radius:12px; margin:20px 0;\" />

<h2>Why Meal Prep?</h2>
<p>Save time, reduce stress, save money, and make healthier choices. When your meals are ready to go, you're less likely to order takeout.</p>

<h2>Step 1: Plan Your Menu</h2>
<p>Choose 3-4 recipes that use overlapping ingredients to reduce waste. Pick one protein (chicken, tofu, beans), one grain (rice, quinoa), and 2-3 vegetables.</p>

<h2>Step 2: Shop Smart</h2>
<p>Make a detailed grocery list organized by aisle. Stick to your list to avoid impulse buys. Buy in bulk for staples like rice and oats.</p>

<h2>Step 3: Batch Cook</h2>
<p>Set aside 2-3 hours on Sunday. Cook grains in a large batch, roast vegetables on sheet pans, and grill or bake proteins all at once.</p>

<h2>Step 4: Portion & Store</h2>
<p>Use glass containers with tight lids. Portion into individual servings so you can grab and go. Label with contents and date.</p>

<h2>Sample Meal Plan</h2>
<ul>
  <li><strong>Monday:</strong> Grilled chicken + quinoa + roasted broccoli</li>
  <li><strong>Tuesday:</strong> Turkey chili with mixed vegetables</li>
  <li><strong>Wednesday:</strong> Salmon + sweet potato + green beans</li>
  <li><strong>Thursday:</strong> Tofu stir-fry with brown rice</li>
  <li><strong>Friday:</strong> Leftover buffet — mix and match!</li>
</ul>

<p><strong>Pro Tip:</strong> Invest in good-quality containers and a reliable food scale for consistent portions.</p>",
                'tags' => ['meal-prep', 'busy-professionals', 'healthy-eating', 'time-saving'],
            ],
            [
                'title' => 'Seasonal Eating: Why Fresh Produce Matters More Than You Think',
                'category' => 'Seasonal',
                'content' => "<p>Eating with the seasons isn't just a trend — it's a smarter, tastier, and more sustainable way to shop for groceries.</p>

<img src=\"https://images.unsplash.com/photo-1565958011703-44f9829ba187?w=800&h=400&fit=crop\" alt=\"Seasonal produce\" style=\"width:100%; border-radius:12px; margin:20px 0;\" />

<h2>What is Seasonal Eating?</h2>
<p>It means choosing fruits and vegetables that are naturally harvested during the current time of year in your region.</p>

<h2>Benefits of Seasonal Eating</h2>
<ul>
  <li><strong>Better Flavor:</strong> Produce picked at peak ripeness tastes dramatically better.</li>
  <li><strong>More Nutrients:</strong> Freshly harvested produce retains more vitamins and minerals.</li>
  <li><strong>Lower Cost:</strong> Seasonal abundance means lower prices at the store.</li>
  <li><strong>Eco-Friendly:</strong> Less transportation and storage required, reducing your carbon footprint.</li>
</ul>

<h2>Spring Produce (March–May)</h2>
<p>Asparagus, artichokes, peas, radishes, strawberries, spinach, and spring onions.</p>

<h2>Summer Produce (June–August)</h2>
<p>Tomatoes, corn, zucchini, peppers, eggplant, berries, melons, and stone fruits.</p>

<h2>Fall Produce (September–November)</h2>
<p>Pumpkins, squash, apples, pears, Brussels sprouts, sweet potatoes, and mushrooms.</p>

<h2>Winter Produce (December–February)</h2>
<p>Kale, citrus fruits, root vegetables, cabbage, broccoli, and cauliflower.</p>

<p><strong>Pro Tip:</strong> Visit your local farmer's market to discover what's in season and support local growers!</p>",
                'tags' => ['seasonal', 'fresh-produce', 'sustainable', 'local-food'],
            ],
            [
                'title' => 'How to Build a Balanced Plate Every Single Time',
                'category' => 'Nutrition',
                'content' => "<p>Understanding how to build a balanced plate is the foundation of healthy eating. No complicated diets — just simple, practical guidelines.</p>

<img src=\"https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=800&h=400&fit=crop\" alt=\"Balanced meal plate\" style=\"width:100%; border-radius:12px; margin:20px 0;\" />

<h2>The Plate Method</h2>
<p>Divide your plate into three sections for perfect portion control every time.</p>

<h2>½ Plate: Vegetables & Fruits</h2>
<p>Fill half your plate with colorful vegetables and fruits. Aim for a variety of colors — the more colors, the wider the range of nutrients. Include leafy greens, bell peppers, carrots, broccoli, or berries.</p>

<h2>¼ Plate: Lean Protein</h2>
<p>One quarter should be protein. Good options include grilled chicken, fish, tofu, eggs, legumes, or lean beef. Protein keeps you full and supports muscle health.</p>

<h2>¼ Plate: Complex Carbs</h2>
<p>The remaining quarter is for healthy carbohydrates like brown rice, quinoa, sweet potatoes, whole wheat pasta, or whole grain bread.</p>

<h2>Don't Forget Healthy Fats</h2>
<p>Add a small serving of healthy fats — avocado slices, a drizzle of olive oil, nuts, or seeds. Fats help absorb fat-soluble vitamins and add flavor.</p>

<h2>Sample Balanced Meals</h2>
<ul>
  <li><strong>Breakfast:</strong> Oatmeal with berries and almonds + scrambled eggs</li>
  <li><strong>Lunch:</strong> Grilled chicken salad with mixed greens, quinoa, and avocado</li>
  <li><strong>Dinner:</strong> Baked salmon + roasted sweet potatoes + steamed broccoli</li>
</ul>

<p><strong>Pro Tip:</strong> Use smaller plates to make portions look more generous and help with mindful eating.</p>",
                'tags' => ['balanced-diet', 'nutrition', 'portion-control', 'healthy-eating'],
            ],
            [
                'title' => 'Organic vs. Conventional Produce: What You Really Need to Know',
                'category' => 'Shopping',
                'content' => "<p>The organic vs. conventional debate can be confusing. Here's an honest breakdown to help you make informed choices.</p>

<img src=\"https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800&h=400&fit=crop\" alt=\"Organic produce\" style=\"width:100%; border-radius:12px; margin:20px 0;\" />

<h2>What Does \"Organic\" Mean?</h2>
<p>Organic produce is grown without synthetic pesticides, chemical fertilizers, GMOs, or sewage sludge. It must meet strict USDA certification standards.</p>

<h2>Nutritional Differences</h2>
<p>Studies show mixed results. Some find slightly higher antioxidant levels in organic produce, but the difference is modest. Both organic and conventional fruits and vegetables are nutritious choices.</p>

<h2>Pesticide Residue — What You Should Know</h2>
<p>The USDA tests show that conventional produce sometimes has pesticide residues, but levels are almost always below safety thresholds. Washing thoroughly removes most residues.</p>

<h2>The Dirty Dozen & Clean Fifteen</h2>
<p>These lists from EWG can help you prioritize:</p>
<ul>
  <li><strong>Buy Organic for:</strong> Strawberries, spinach, kale, nectarines, apples, grapes, peaches, cherries, pears, celery, tomatoes, and bell peppers.</li>
  <li><strong>Conventional is Fine for:</strong> Avocados, sweet corn, pineapples, onions, papayas, frozen peas, asparagus, mangoes, eggplant, honeydew melon, kiwi, and cabbage.</li>
</ul>

<h2>Cost Consideration</h2>
<p>Organic produce typically costs 20-50% more. If budget is tight, prioritize organic for the Dirty Dozen and buy conventional for the Clean Fifteen.</p>

<h2>Bottom Line</h2>
<p>Eating more fruits and vegetables — organic or conventional — is always the healthiest choice. Don't let the organic premium stop you from eating produce.</p>",
                'tags' => ['organic', 'conventional', 'shopping-tips', 'produce'],
            ],
            [
                'title' => 'Kid-Friendly Healthy Snacks They Will Actually Eat',
                'category' => 'Kids',
                'content' => "<p>Getting kids to eat healthy snacks can feel like an uphill battle. These creative ideas are kid-approved and parent-endorsed.</p>

<img src=\"https://images.unsplash.com/photo-1476224203421-9ac39bcb3327?w=800&h=400&fit=crop\" alt=\"Healthy kids snacks\" style=\"width:100%; border-radius:12px; margin:20px 0;\" />

<h2>1. Fruit Kabobs with Yogurt Dip</h2>
<p>Thread strawberries, bananas, grapes, and melon chunks onto skewers. Serve with a side of Greek yogurt mixed with a drizzle of honey. Fun to eat and packed with vitamins.</p>

<h2>2. Veggie Muffins</h2>
<p>Grate zucchini or carrots and mix into muffin batter. They add moisture and nutrients without changing the flavor. Make a batch on Sunday for the week ahead.</p>

<h2>3. Apple \"Donuts\"</h2>
<p>Slice apples crosswise into rings, remove the core, and spread with nut butter. Top with granola, raisins, or a few chocolate chips. Looks like a treat but is all healthy.</p>

<h2>4. Frozen Yogurt Bites</h2>
<p>Spoon dollops of Greek yogurt onto a baking sheet, top with berries, and freeze. These bite-sized frozen treats are perfect for hot days and much healthier than ice cream.</p>

<h2>5. Cheese and Veggie Roll-Ups</h2>
<p>Spread cream cheese on a whole wheat tortilla, add thinly sliced cucumber or bell pepper strips, roll tightly, and slice into pinwheels.</p>

<h2>6. Homemade Trail Mix</h2>
<p>Let kids build their own mix with whole grain cereal, dried fruit, pretzels, and a few chocolate chips. Skip the nuts if there are allergy concerns.</p>

<h2>7. Banana Popsicles</h2>
<p>Cut bananas in half, insert a popsicle stick, dip in yogurt, then roll in crushed cereal or coconut flakes. Freeze for 2 hours for a creamy, healthy treat.</p>

<p><strong>Pro Tip:</strong> Get kids involved in making their snacks. When they help prepare food, they're more likely to eat it!</p>",
                'tags' => ['kids', 'snacks', 'healthy-eating', 'parenting'],
            ],
        ];

        foreach ($articles as $i => $article) {
            Article::updateOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($article['title'])],
                [
                    'nutritionist_id' => $nutritionist->id,
                    'title' => $article['title'],
                    'content' => $article['content'],
                    'category' => $article['category'],
                    'image_url' => $foodImages[$i % count($foodImages)],
                    'tags' => $article['tags'],
                    'is_published' => true,
                    'published_at' => now()->subDays(count($articles) - $i),
                ]
            );
        }

        $this->command->info('Seeded ' . count($articles) . ' blog articles.');
    }
}
