<?php
// 1. Shopify Credentials
$store_domain = "wmkvgp-mv.myshopify.com";
$token = "aa8e850c9d2a26c4ad60a76dbd151695";

// 2. GraphQL Query
$graphql_query = json_encode([
    'query' => '{
        products(first: 10) {
            edges {
                node {
                    title
                    descriptionHtml
                    images(first: 1) {
                        edges { node { url } }
                    }
                    variants(first: 1) {
                        edges {
                            node {
                                id
                                price { amount currencyCode }
                            }
                        }
                    }
                }
            }
        }
    }'
]);

// 3. API Request Send Karna
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => "https://$store_domain/api/2024-01/graphql.json",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS => $graphql_query,
  CURLOPT_HTTPHEADER => array(
    "Content-Type: application/json",
    "X-Shopify-Storefront-Access-Token: $token"
  ),
));

$response = curl_exec($curl);
// curl_close() is removed to prevent PHP 8 deprecation warning

$data = json_decode($response, true);
$products = $data['data']['products']['edges'] ?? [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Products</title>
    <style>
        /* Modern UI/UX CSS */
        body { 
            font-family: system-ui, -apple-system, sans-serif; 
            background-color: #f8f9fa; 
            margin: 0; 
            padding: 20px; 
        }
        .container { 
            max-width: 800px; 
            margin: 0 auto; 
        }
        .header-title {
            text-align: center;
            color: #1a1a1a;
            margin-bottom: 40px;
            font-size: 28px;
            font-weight: 700;
        }
        
        /* Line-by-Line Product Card Style */
        .product-card { 
            background: #ffffff; 
            border-radius: 12px; 
            padding: 20px; 
            margin-bottom: 20px; 
            display: flex; 
            align-items: center; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.04); 
            border: 1px solid #eaeaea;
            transition: transform 0.2s, box-shadow 0.2s; 
        }
        .product-card:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 8px 15px rgba(0,0,0,0.08); 
        }
        
        /* Image Styling */
        .product-image { 
            width: 160px; 
            height: 160px; 
            border-radius: 8px; 
            object-fit: cover; 
            margin-right: 24px; 
            background-color: #f4f4f4;
        }
        
        /* Text & Button Styling */
        .product-details { 
            flex: 1; 
        }
        .product-title { 
            margin: 0 0 8px 0; 
            font-size: 22px; 
            color: #2d3748; 
        }
        .product-desc { 
            color: #718096; 
            font-size: 14px; 
            margin-bottom: 16px; 
            display: -webkit-box; 
            -webkit-line-clamp: 2; 
            -webkit-box-orient: vertical; 
            overflow: hidden; 
        }
        .product-price { 
            font-size: 20px; 
            font-weight: 700; 
            color: #10b981; 
            margin-bottom: 16px; 
        }
        .buy-btn { 
            background-color: #000000; 
            color: #ffffff; 
            padding: 10px 24px; 
            text-decoration: none; 
            border-radius: 6px; 
            font-weight: 600; 
            display: inline-block; 
            transition: background 0.2s; 
        }
        .buy-btn:hover { 
            background-color: #333333; 
        }
        
        /* Mobile Responsive */
        @media(max-width: 600px) {
            .product-card { 
                flex-direction: column; 
                text-align: center; 
            }
            .product-image { 
                margin: 0 0 20px 0; 
                width: 100%; 
                height: 220px; 
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <h1 class="header-title">Our Premium Instruments</h1>
        
        <?php if (!empty($products)): ?>
            <?php foreach ($products as $item): 
                $product = $item['node'];
                
                // 1. Data Extract Karna
                $title = $product['title'];
                $price = $product['variants']['edges'][0]['node']['price']['amount'];
                
                // Agar image nahi hai toh ek default blank image lag jayegi
                $image_url = isset($product['images']['edges'][0]['node']['url']) 
                             ? $product['images']['edges'][0]['node']['url'] 
                             : 'https://via.placeholder.com/150?text=No+Image';
                             
                // HTML tags hatane ke liye strip_tags use kiya
                $description = strip_tags($product['descriptionHtml']); 
                
                // 2. Variant ID alag karna (Checkout URL ke liye)
                $raw_variant_id = $product['variants']['edges'][0]['node']['id'];
                $variant_id = basename($raw_variant_id); 
                
                // 3. Direct Checkout Permalink
                $checkout_url = "https://wmkvgp-mv.myshopify.com/cart/{$variant_id}:1";
            ?>
            
            <div class="product-card">
                <img src="<?= $image_url ?>" alt="<?= htmlspecialchars($title) ?>" class="product-image">
                
                <div class="product-details">
                    <h2 class="product-title"><?= htmlspecialchars($title) ?></h2>
                    <div class="product-desc"><?= htmlspecialchars($description) ?></div>
                    <div class="product-price">₹<?= number_format($price, 2) ?></div>
                    <a href="<?= $checkout_url ?>" class="buy-btn">Buy Now</a>
                </div>
            </div>
            
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align:center; color: #666;">No products found in the store.</p>
        <?php endif; ?>
        
    </div>

</body>
</html>
