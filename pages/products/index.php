<?php
// PRODUCTS PAGE - ENHANCED VERSION
require_once '../../config.php';
require_once '../../includes/classes/Database.php';

// Function to convert USD to CFA
function usdToCFA($usd) {
    return number_format($usd * 600, 0, '.', ','); // Example: 1 USD = 600 CFA
}

$db = Database::getInstance();

// 1. You MUST define the query FIRST
$db->query("SELECT * FROM products ORDER BY created_at DESC"); 

// 2. THEN you can get the results
$products = $db->resultSet();

// Get query parameters
$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$max_price = $_GET['max_price'] ?? '';

// Sample clothing categories
$categories = [
    'traditional' => ['name' => 'Traditional Wear', 'icon' => 'tshirt', 'count' => 8],
    'modern' => ['name' => 'Modern Fashion', 'icon' => 'suitcase', 'count' => 7],
    'wedding' => ['name' => 'Wedding Attire', 'icon' => 'ring', 'count' => 6],
    'formal' => ['name' => 'Formal Wear', 'icon' => 'briefcase', 'count' => 5],
    'custom' => ['name' => 'Custom Designs', 'icon' => 'pencil-square', 'count' => 4],
    'casual' => ['name' => 'Casual Wear', 'icon' => 'tshirt', 'count' => 9],
    'accessories' => ['name' => 'Accessories', 'icon' => 'gem', 'count' => 5],
    'kids' => ['name' => 'Kids Wear', 'icon' => 'child', 'count' => 4]
];

// Comprehensive sample products data
$sampleProducts = [
    // Traditional Wear (8 items)
    ['id' => 1, 'category' => 'traditional', 'title' => 'Djalabia', 'price' => 89.99, 'Abdour-Raouf' => 'Aisha Designs', 'rating' => 4.8, 'description' => 'Beautiful hand-printed kaftan with traditional African patterns.'],
    ['id' => 2, 'category' => 'traditional', 'title' => 'Indian Silk Saree', 'price' => 149.99, 'Moussa Hamani' => 'Bollywood Tailors', 'rating' => 4.9, 'description' => 'Premium silk saree with intricate embroidery and gold thread work.'],
    ['id' => 3, 'category' => 'traditional', 'title' => 'Japanese Kimono', 'price' => 199.99, 'Ibrahim Harounna' => 'Tokyo Crafts', 'rating' => 4.7, 'description' => 'Authentic Japanese kimono made from premium silk fabric.'],
    ['id' => 4, 'category' => 'traditional', 'title' => 'Chinese Cheongsam', 'price' => 129.99, 'Ila Soumeila' => 'Shanghai Silk', 'rating' => 4.6, 'description' => 'Elegant cheongsam dress with traditional Chinese embroidery.'],
    ['id' => 5, 'category' => 'traditional', 'title' => 'Arab Thobe', 'price' => 79.99, 'Tanimou Hamma' => 'Desert Tailors', 'rating' => 4.5, 'description' => 'Traditional Arab thobe made from lightweight cotton fabric.'],
    ['id' => 6, 'category' => 'traditional', 'title' => 'Korean Hanbok', 'price' => 169.99, 'Sara Halidou' => 'Seoul Fashion', 'rating' => 4.8, 'description' => 'Colorful Korean hanbok with vibrant colors and patterns.'],
    ['id' => 7, 'category' => 'traditional', 'title' => 'Scottish Kilt', 'price' => 189.99, 'Faouma Amani' => 'Highland Crafts', 'rating' => 4.7, 'description' => 'Authentic Scottish kilt with clan tartan pattern.'],
    ['id' => 8, 'category' => 'traditional', 'title' => 'Mexican Poncho', 'price' => 59.99, 'Zahara Sani' => 'Aztec Designs', 'rating' => 4.4, 'description' => 'Warm Mexican poncho with traditional geometric patterns.'],
    
    // Modern Fashion (7 items)
    ['id' => 9, 'category' => 'modern', 'title' => 'Designer Blazer', 'price' => 129.99, 'tailor_name' => 'Urban Stitch', 'rating' => 4.6, 'description' => 'Modern tailored blazer for business meetings.'],
    ['id' => 10, 'category' => 'modern', 'title' => 'Leather Jacket', 'price' => 159.99, 'tailor_name' => 'Leather Masters', 'rating' => 4.5, 'description' => 'Handcrafted genuine leather jacket with premium finish.'],
    ['id' => 11, 'category' => 'modern', 'title' => 'Evening Gown', 'price' => 229.99, 'tailor_name' => 'Glamour Stitch', 'rating' => 4.9, 'description' => 'Elegant evening gown with sequin details and silk lining.'],
    ['id' => 12, 'category' => 'modern', 'title' => 'Designer Dress', 'price' => 99.99, 'tailor_name' => 'Fashion Hub', 'rating' => 4.4, 'description' => 'Contemporary designer dress with unique cut and pattern.'],
    ['id' => 13, 'category' => 'modern', 'title' => 'Tailored Suit', 'price' => 299.99, 'tailor_name' => 'Premium Tailors', 'rating' => 4.8, 'description' => 'Custom tailored three-piece suit for formal occasions.'],
    ['id' => 14, 'category' => 'modern', 'title' => 'Designer Jumpsuit', 'price' => 89.99, 'tailor_name' => 'Modern Wear', 'rating' => 4.3, 'description' => 'Trendy jumpsuit with elegant design and comfortable fit.'],
    ['id' => 15, 'category' => 'modern', 'title' => 'Linen Set', 'price' => 109.99, 'tailor_name' => 'Linen Studio', 'rating' => 4.6, 'description' => 'Premium linen shirt and pants set for summer wear.'],
    
    // FORMAL CATEGORY (African Formal Wear)
    ['id' => 40, 'category' => 'formal', 'title' => 'Premium Midnight Agbada', 'price' => 125000, 'tailor_name' => 'Lagos Luxe', 'rating' => 5.0, 'description' => 'Four-piece royal Agbada set with silver hand-embroidery.'],
    ['id' => 41, 'category' => 'formal', 'title' => 'Ivory Senator Suit', 'price' => 55000, 'tailor_name' => 'Dakar Designs', 'rating' => 4.8, 'description' => 'Polished Senator wear with modern slim-fit cut.'],
    ['id' => 42, 'category' => 'formal', 'title' => 'Silk Kaftan Evening Gown', 'price' => 85000, 'tailor_name' => 'Accra Chic', 'rating' => 4.9, 'description' => 'Elegant floor-length silk gown with gold patterns.'],
    ['id' => 43, 'category' => 'formal', 'title' => 'Embroidered Brocade Suit', 'price' => 140000, 'tailor_name' => 'Royal Stitches', 'rating' => 4.7, 'description' => 'Luxury brocade fabric tailored formal suit.'],
    ['id' => 44, 'category' => 'formal', 'title' => 'Couple Suit', 'price' => 300000, 'tailor_name' => 'Royal Stitches', 'rating' => 4.7, 'description' => 'Matching couple formal wear set.'],

    // CUSTOM CATEGORY (Bespoke/Made-to-Measure)
    ['id' => 45, 'category' => 'custom', 'title' => 'Bespoke Corporate Set', 'price' => 175000, 'tailor_name' => 'Master Stitch', 'rating' => 5.0, 'description' => 'Fully customized 3-piece suit to your measurements.'],
    ['id' => 46, 'category' => 'custom', 'title' => 'Hand-Painted Silk Kaftan', 'price' => 65000, 'tailor_name' => 'Artisanal Wear', 'rating' => 4.9, 'description' => 'Unique hand-painted batik patterns on silk.'],
    ['id' => 47, 'category' => 'custom', 'title' => 'Custom Kente Wedding Gown', 'price' => 250000, 'tailor_name' => 'Heritage Couture', 'rating' => 5.0, 'description' => 'Hand-woven Kente fabric bridal masterpiece.'],
    ['id' => 48, 'category' => 'custom', 'title' => 'Tailored Dashiki Blazer', 'price' => 45000, 'tailor_name' => 'Urban African', 'rating' => 4.6, 'description' => 'Custom-fit blazer with Dashiki prints.'],

    // Wedding Attire (6 items)
    ['id' => 16, 'category' => 'wedding', 'title' => 'Bridal Wedding Dress', 'price' => 499.99, 'tailor_name' => 'Bridal Couture', 'rating' => 5.0, 'description' => 'Custom made wedding dress with lace details.'],
    ['id' => 17, 'category' => 'wedding', 'title' => 'Groom Suit', 'price' => 299.99, 'tailor_name' => 'Gentleman Tailors', 'rating' => 4.8, 'description' => 'Tailored three-piece wedding suit with silk lining.'],
    ['id' => 18, 'category' => 'wedding', 'title' => 'Bridesmaid Dress', 'price' => 129.99, 'tailor_name' => 'Party Style', 'rating' => 4.7, 'description' => 'Beautiful bridesmaid dress in various colors.'],
    ['id' => 19, 'category' => 'wedding', 'title' => 'Mother of Bride Dress', 'price' => 179.99, 'tailor_name' => 'Elegant Wear', 'rating' => 4.6, 'description' => 'Elegant dress for mother of the bride.'],
    ['id' => 20, 'category' => 'wedding', 'title' => 'Flower Girl Dress', 'price' => 69.99, 'tailor_name' => 'Little Princess', 'rating' => 4.9, 'description' => 'Adorable flower girl dress with lace details.'],
    ['id' => 21, 'category' => 'wedding', 'title' => 'Groomsmen Set', 'price' => 199.99, 'tailor_name' => 'Formal Wear', 'rating' => 4.5, 'description' => 'Complete groomsmen suit set for wedding party.'],
    
    // Casual Wear (9 items)
    ['id' => 22, 'category' => 'casual', 'title' => 'Denim Jeans', 'price' => 59.99, 'tailor_name' => 'Denim Factory', 'rating' => 4.4, 'description' => 'Custom fit denim jeans with premium fabric.'],
    ['id' => 23, 'category' => 'casual', 'title' => 'Cotton T-Shirt', 'price' => 24.99, 'tailor_name' => 'Comfort Wear', 'rating' => 4.3, 'description' => 'Premium cotton t-shirt with custom print options.'],
    ['id' => 24, 'category' => 'casual', 'title' => 'Summer Dress', 'price' => 49.99, 'tailor_name' => 'Sunny Designs', 'rating' => 4.6, 'description' => 'Light summer dress with floral pattern.'],
    ['id' => 25, 'category' => 'casual', 'title' => 'Hoodie', 'price' => 44.99, 'tailor_name' => 'Cozy Wear', 'rating' => 4.5, 'description' => 'Comfortable hoodie with front pocket.'],
    ['id' => 26, 'category' => 'casual', 'title' => 'Cargo Pants', 'price' => 54.99, 'tailor_name' => 'Urban Comfort', 'rating' => 4.4, 'description' => 'Practical cargo pants with multiple pockets.'],
    ['id' => 27, 'category' => 'casual', 'title' => 'Polo Shirt', 'price' => 34.99, 'tailor_name' => 'Sport Style', 'rating' => 4.3, 'description' => 'Classic polo shirt for casual occasions.'],
    ['id' => 28, 'category' => 'casual', 'title' => 'Sweatpants', 'price' => 39.99, 'tailor_name' => 'Relax Wear', 'rating' => 4.5, 'description' => 'Comfortable sweatpants for lounging.'],
    ['id' => 29, 'category' => 'casual', 'title' => 'Shorts', 'price' => 29.99, 'tailor_name' => 'Summer Style', 'rating' => 4.4, 'description' => 'Comfortable shorts for summer weather.'],
    ['id' => 30, 'category' => 'casual', 'title' => 'Cardigan', 'price' => 49.99, 'tailor_name' => 'Cozy Knits', 'rating' => 4.6, 'description' => 'Lightweight cardigan for layering.'],
    
    // Accessories (5 items)
    ['id' => 31, 'category' => 'accessories', 'title' => 'Leather Handbag', 'price' => 89.99, 'tailor_name' => 'Leather Craft', 'rating' => 4.7, 'description' => 'Handmade leather handbag with compartments.'],
    ['id' => 32, 'category' => 'accessories', 'title' => 'Silk Scarf', 'price' => 34.99, 'tailor_name' => 'Silk Studio', 'rating' => 4.5, 'description' => 'Printed silk scarf with vibrant patterns.'],
    ['id' => 33, 'category' => 'accessories', 'title' => 'Designer Belt', 'price' => 44.99, 'tailor_name' => 'Accessory Hub', 'rating' => 4.4, 'description' => 'Handcrafted leather belt with metal buckle.'],
    ['id' => 34, 'category' => 'accessories', 'title' => 'Wool Hat', 'price' => 29.99, 'tailor_name' => 'Winter Wear', 'rating' => 4.3, 'description' => 'Warm wool hat for winter season.'],
    ['id' => 35, 'category' => 'accessories', 'title' => 'Leather Wallet', 'price' => 39.99, 'tailor_name' => 'Leather Goods', 'rating' => 4.6, 'description' => 'Genuine leather wallet with card slots.'],
    
    // Kids Wear (4 items)
    ['id' => 36, 'category' => 'kids', 'title' => 'Kids Traditional Set', 'price' => 39.99, 'tailor_name' => 'Kids Fashion', 'rating' => 4.8, 'description' => 'Traditional outfit set for children.'],
    ['id' => 37, 'category' => 'kids', 'title' => 'Baby Romper', 'price' => 19.99, 'tailor_name' => 'Baby Comfort', 'rating' => 4.6, 'description' => 'Comfortable baby romper with snap buttons.'],
    ['id' => 38, 'category' => 'kids', 'title' => 'Children Jacket', 'price' => 34.99, 'tailor_name' => 'Kids Warm', 'rating' => 4.5, 'description' => 'Warm jacket for children with hood.'],
    ['id' => 39, 'category' => 'kids', 'title' => 'Kids Jeans', 'price' => 24.99, 'tailor_name' => 'Little Denim', 'rating' => 4.4, 'description' => 'Durable jeans for active children.']
];

// Clothing images for each category
$clothingImages = [
    'traditional' => [
        'https://tse4.mm.bing.net/th/id/OIP.n83ZJZpGe48lnhNHGCqAXgHaJT?rs=1&pid=ImgDetMain&o=7&rm=3',
        'https://th.bing.com/th/id/OIP.U5awbI4cQTQGtz6Z6J16pAHaHa?w=201&h=201&c=7&r=0&o=7&dpr=2.6&pid=1.7&rm=3',
        'https://th.bing.com/th?q=Traditional+Muslim+Clothing+for+Women&w=120&h=120&c=1&rs=1&qlt=70&o=7&cb=1&dpr=2.6&pid=InlineBlock&rm=3&mkt=en-WW&cc=GH&setlang=en&adlt=moderate&t=1&mw=247',
        'https://villagegreennj.com/wp-content/uploads/2021/01/IMG_0532-scaled.jpg',
        'https://th.bing.com/th/id/OIP.QwWWJ0_m081_h0EfnKtnRwHaLy?w=119&h=189&c=7&r=0&o=7&dpr=2.6&pid=1.7&rm=3'
    ],
    'modern' => [
        'https://i.pinimg.com/736x/02/b4/20/02b42014235cb794b63a691489a38d6d.jpg',
        'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ7SpOiWlnJqVlDYXxD2FzVZelc6SyjZu438w&s',
        'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSKMtkalpFG1TQ7tvECLEWxqdiJ_HswOAKBBA&s',
        'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQSt43JRLaxscPrISLPxyiLXQyMNSSC2sa7Eg&s',
        'https://www.cartrollers.com/product/quality-islamic-fashion-jalabiya',
        'https://i.pinimg.com/736x/39/76/ae/3976aef31f9923d483b5dcac386fe75e.jpg',
        'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRjN3tZOJ8lYuEi9UcJptDJafmMIPZ8z2R7zA&s',
        'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSCzWoUuRWxepK4rbM5fgbkIAPSYHInpv7pXA&s'
    ],
    'formal'=> [
        'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTNA-BrhdLtyPMog2YDzb0q7dw2WYSNM9-gEA&s',
        'https://otunbastore.com/cdn/shop/products/il_fullxfull.5295786566_n7me.jpg?v=1703837852',
        'https://africablooms.com/wp-content/uploads/2019/05/African-Clothing-for-Boys-Blue-Dashiki-for-Boys-Agbada-AFRICA-BLOOMS.jpg',
        'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRalbkBz9VGB1wNBy6PwNxN2Zq7Z54qjn1rag&s',
        'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSjCsjrIqBP1FXTuYYgGsyFkH71oOC6Ei0GUw&s'
    ],
    'custom' => [
        'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS4DA8F2FRQJ-KIxZEtIvU2yWehgPtM5KVH4w&s',
        'https://i.etsystatic.com/24512336/r/il/35f8b3/2450679742/il_570xN.2450679742_gkc1.jpg',
        'https://i.pinimg.com/736x/d4/1d/09/d41d093e1de4ef460e1cd0a1f5433f5c.jpg',
        'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS2QYYUNcdqnvw-UZ60tLDSjiHIVSa6u0MDjg&s',
        'https://i.etsystatic.com/18339637/r/il/cd0db0/5547987457/il_570xN.5547987457_1vs0.jpg'
    ],
    'wedding' => [
        'https://i.pinimg.com/736x/6c/00/af/6c00af45b0b1e933badc4eb22408659b.jpg',
        'https://m.media-amazon.com/images/I/61hNb+XIfDL._AC_UY1000_.jpg',
        'https://d17a17kld06uk8.cloudfront.net/products/X86BY2I/ZA6QXPH7-original.jpg',
        'https://s.alicdn.com/@sc04/kf/H231f9a12d3bc44c0863f50c048df226eh.jpg_300x300.jpg',
        'https://i.etsystatic.com/18339637/r/il/cd0db0/5547987457/il_570xN.5547987457_1vs0.jpg'
    ],
    'casual' => [
        'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR706WGKqFUc3xuc5prIczMrCRRKOg6uyVHOQ&s',
        'https://img4.dhresource.com/webp/m/0x0/f3/albu/km/l/24/cb6cdc49-c4db-4eb7-ab4a-39864aab289c.jpg',
        'https://pictures-ghana.jijistatic.net/55589937_NjIwLTExMDAtYzk5NjcwN2UxZQ.webp',
        'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTNgU4HLxXfNvPIpyfkW3NRMcs-LZYBw1EVBw&s',
        'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSgG1GajKBVl7S7GZtjbSV-mjjhtq6JYUJNQw&s'
    ],
    'accessories' => [
        'https://i.etsystatic.com/25035448/r/il/e24add/4723659294/il_600x600.4723659294_jaza.jpg',
        'https://tse3.mm.bing.net/th/id/OIP.pVcixOOnKP5NNWCVDDM9tQHaFj?rs=1&pid=ImgDetMain&o=7&rm=3',
        '',
        'https://tse3.mm.bing.net/th/id/OIP._6Jxs8fODoHLESoYN3ccRQHaHa?rs=1&pid=ImgDetMain&o=7&rm=3',
        'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRfsUI2cgq6FzxO_X2UjtJG8KFX-6nyxxh71A&s'
    ],
    'kids' => [
        'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcThQ-MnyxlnAcX7cIRQM5MmtpMpl-BThIG_BA&s',
        'https://i.etsystatic.com/25411016/r/il/12b16d/5693229335/il_570xN.5693229335_5pij.jpg',
        'https://i.etsystatic.com/50093480/r/il/d35108/6998100785/il_fullxfull.6998100785_71a6.jpg',
        'https://i.pinimg.com/236x/18/de/c7/18dec7fa9b5af77dfcd01c17538a86ae.jpg',
        'https://i.pinimg.com/736x/53/0f/9e/530f9e0e47f2233e22fbb8676aa5c2ae.jpg'
    ]
];

// Process products
try {
    $sql = "SELECT p.*, u.username as tailor_name FROM products p 
            LEFT JOIN users u ON p.tailor_id = u.id 
            WHERE p.status = 'active'";
    
    $params = [];
    
    if ($category) {
        $sql .= " AND p.category = ?";
        $params[] = $category;
    }
    
    if ($max_price) {
        $sql .= " AND p.price <= ?";
        $params[] = $max_price;
    }
    
    // Sorting
    $sortMap = [
        'price_low' => 'p.price ASC',
        'price_high' => 'p.price DESC', 
        'rating' => 'p.rating DESC',
        'popular' => 'p.view_count DESC'
    ];
    $sql .= " ORDER BY " . ($sortMap[$sort] ?? 'p.created_at DESC');
    
    $db = Database::getInstance();
    $db->query($sql);
    foreach ($params as $i => $param) {
        $db->bind(":param" . ($i + 1), $param);
    }
    $products = $db->fetchAll();
    
    // If no products in database, use sample data
    if (empty($products)) {
        $products = array_filter($sampleProducts, function($product) use ($category, $max_price) {
            $categoryMatch = !$category || $product['category'] === $category;
            $priceMatch = !$max_price || $product['price'] <= $max_price;
            return $categoryMatch && $priceMatch;
        });
        
        // Apply sorting to sample data
        if ($sort === 'price_low') {
            usort($products, fn($a, $b) => $a['price'] <=> $b['price']);
        } elseif ($sort === 'price_high') {
            usort($products, fn($a, $b) => $b['price'] <=> $a['price']);
        } elseif ($sort === 'rating') {
            usort($products, fn($a, $b) => $b['rating'] <=> $a['rating']);
        }
    }
    
    $product_count = count($products);
    
    // Calculate category counts
    foreach ($categories as $cat => &$catInfo) {
        $catInfo['count'] = count(array_filter($sampleProducts, fn($p) => $p['category'] === $cat));
    }
    
} catch (Exception $e) {
    // Use sample data if database fails
    $products = array_filter($sampleProducts, function($product) use ($category, $max_price) {
        $categoryMatch = !$category || $product['category'] === $category;
        $priceMatch = !$max_price || $product['price'] <= $max_price;
        return $categoryMatch && $priceMatch;
    });
    $product_count = count($products);
}

// Calculate stats
$total_products = count($sampleProducts);
$average_price_usd = array_sum(array_column($sampleProducts, 'price')) / $total_products;
$average_price_cfa = $average_price_usd * 600; // Convert to CFA
$average_rating = array_sum(array_column($sampleProducts, 'rating')) / $total_products;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $category ? $categories[$category]['name'] : 'All'; ?> Products - Tailor Shop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    :root {
        --primary: #667eea;
        --secondary: #764ba2;
    }
    
    body {
        background: #f8fafc;
        font-family: 'Segoe UI', system-ui, sans-serif;
    }
    
    .page-header {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        padding: 3rem 0;
        position: relative;
        overflow: hidden;
    }
    
    .page-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.1' fill-rule='evenodd'/%3E%3C/svg%3E");
        opacity: 0.3;
    }
    
    /* Enhanced Category Navigation */
    .category-nav-v2 {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        margin: -2rem auto 2rem;
        max-width: 1200px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        position: relative;
        z-index: 10;
    }
    
    .category-btn-v2 {
        padding: 1rem 1.25rem;
        border-radius: 12px;
        background: #f8fafc;
        color: #475569;
        font-weight: 500;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s;
        border: 2px solid transparent;
        height: 100%;
    }
    
    .category-btn-v2:hover, .category-btn-v2.active {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
    }
    
    .category-count-v2 {
        background: rgba(255,255,255,0.2);
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 0.8rem;
        margin-left: auto;
    }
    
    /* Enhanced Products Grid */
    .products-grid-v2 {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 25px;
        margin: 2rem 0;
    }
    
    .product-card-v2 {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.1);
        transition: all 0.3s ease;
        border: 1px solid #e2e8f0;
        position: relative;
        height: 100%;
    }
    
    .product-card-v2:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(102, 126, 234, 0.15);
        border-color: var(--primary);
    }
    
    .product-img-container-v2 {
        height: 200px;
        overflow: hidden;
        position: relative;
    }
    
    .product-img-v2 {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.6s ease;
    }
    
    .product-card-v2:hover .product-img-v2 {
        transform: scale(1.08);
    }
    
    .product-badge-v2 {
        position: absolute;
        top: 12px;
        left: 12px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        padding: 6px 15px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        z-index: 2;
        letter-spacing: 0.5px;
    }
    
    .product-info-v2 {
        padding: 1.25rem;
        flex-grow: 1;
    }
    
    .product-title-v2 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.75rem;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 3rem;
    }
    
    .product-description-v2 {
        color: #64748b;
        font-size: 0.85rem;
        line-height: 1.5;
        margin-bottom: 1rem;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 2.5rem;
    }
    
    .product-price-v2 {
        font-size: 1.4rem;
        font-weight: 700;
        color: var(--primary);
        margin: 1rem 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .product-price-v2 .currency {
        font-size: 1rem;
        color: #64748b;
    }
    
    .product-price-v2 .cfa {
        font-size: 0.9rem;
        color: #64748b;
        margin-left: auto;
    }
    
    .product-meta-v2 {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 1rem;
        border-top: 1px solid #e2e8f0;
        margin-top: auto;
    }
    
    .tailor-info-v2 {
        font-size: 0.85rem;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .rating-v2 {
        color: #f59e0b;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 3px;
    }
    
    .product-actions-v2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-top: 1rem;
    }
    
    .btn-view-v2, .btn-cart-v2 {
        padding: 0.75rem;
        border-radius: 10px;
        font-weight: 500;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-size: 0.9rem;
    }
    
    .btn-view-v2 {
        background: white;
        color: var(--primary);
        border: 2px solid var(--primary);
    }
    
    .btn-view-v2:hover {
        background: var(--primary);
        color: white;
        transform: translateY(-2px);
    }
    
    .btn-cart-v2 {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
    }
    
    .btn-cart-v2:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }
    
    /* Shop Stats Card */
    .stats-card-v2 {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        margin-bottom: 1.5rem;
        border: 1px solid #e2e8f0;
    }
    
    .stats-card-v2 i {
        font-size: 2.5rem;
        color: var(--primary);
        margin-bottom: 1rem;
    }
    
    .stats-card-v2 h4 {
        color: #1e293b;
        font-size: 1.2rem;
        margin-bottom: 1rem;
    }
    
    .stats-values-v2 {
        text-align: left;
    }
    
    .stat-item-v2 {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .stat-item-v2:last-child {
        border-bottom: none;
    }
    
    .stat-label {
        color: #64748b;
        font-size: 0.9rem;
    }
    
    .stat-value {
        font-weight: 600;
        color: #1e293b;
    }
    
    /* Price Filter */
    .price-filter-v2 {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        margin-bottom: 1.5rem;
    }
    
    .price-slider-v2 {
        width: 100%;
        height: 8px;
        border-radius: 4px;
        background: #e2e8f0;
        outline: none;
        -webkit-appearance: none;
        margin: 1rem 0;
    }
    
    .price-slider-v2::-webkit-slider-thumb {
        -webkit-appearance: none;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: var(--primary);
        cursor: pointer;
        border: 3px solid white;
        box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
    }
    
    .price-values-v2 {
        display: flex;
        justify-content: space-between;
        margin-top: 10px;
        color: #64748b;
        font-size: 0.9rem;
    }
    
    /* Sort Options */
    .sort-options-v2 {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    }
    
    .sort-list-v2 {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .sort-item-v2 {
        padding: 0.75rem 1rem;
        border-radius: 10px;
        margin-bottom: 0.5rem;
        transition: all 0.3s;
    }
    
    .sort-item-v2:hover, .sort-item-v2.active {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
    }
    
    .sort-item-v2 a {
        color: inherit;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    /* Results Header */
    .results-header-v2 {
        background: white;
        border-radius: 15px;
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.5rem;
        border: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    /* Cart Notification */
    .cart-notification-v2 {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: white;
        border-radius: 12px;
        padding: 1.25rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        z-index: 1000;
        display: flex;
        align-items: center;
        gap: 15px;
        transform: translateY(100px);
        opacity: 0;
        transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        border: 1px solid #e2e8f0;
        max-width: 350px;
    }
    
    .cart-notification-v2.show {
        transform: translateY(0);
        opacity: 1;
    }
    
    .notification-icon {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
    }
    
    /* Empty State */
    .empty-state-v2 {
        text-align: center;
        padding: 4rem 2rem;
        background: white;
        border-radius: 15px;
        border: 1px solid #e2e8f0;
    }
    
    .empty-state-v2 i {
        font-size: 4rem;
        color: #cbd5e1;
        margin-bottom: 1.5rem;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .products-grid-v2 {
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .product-actions-v2 {
            grid-template-columns: 1fr;
        }
        
        .category-nav-v2 {
            margin: 1rem 0 2rem;
            padding: 1rem;
        }
        
        .category-btn-v2 {
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
        }
        
        .results-header-v2 {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }
    }
    
    /* Price Display */
    .price-display {
        display: flex;
        align-items: baseline;
        gap: 5px;
        flex-wrap: wrap;
    }
    
    .price-main {
        font-size: 1.4rem;
        font-weight: 700;
        color: var(--primary);
    }
    
    .price-secondary {
        font-size: 0.9rem;
        color: #64748b;
        text-decoration: line-through;
        opacity: 0.8;
    }

        /* Custom Order Section */
    .custom-order-section {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-radius: 20px;
        padding: 3rem;
        margin-top: 3rem;
        border: 1px solid #e2e8f0;
    }

    .custom-order-card {
        background: white;
        border-radius: 15px;
        padding: 2.5rem;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.1);
    }

    .custom-order-card h2 {
        color: #1e293b;
        font-weight: 700;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .features-list {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .feature-item {
        display: flex;
        gap: 1rem;
        align-items: flex-start;
    }

    .feature-item i {
        font-size: 1.5rem;
        margin-top: 0.25rem;
    }

    .feature-item h5 {
        color: #1e293b;
        margin-bottom: 0.25rem;
        font-size: 1.1rem;
    }

    .feature-item p {
        color: #64748b;
        font-size: 0.9rem;
        margin: 0;
    }

    .btn-custom-order {
        display: inline-flex;
        align-items: center;
        padding: 1rem 2.5rem;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        border: none;
        border-radius: 12px;
        font-weight: 600;
        font-size: 1.1rem;
        text-decoration: none;
        transition: all 0.3s ease;
        box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
    }

    .btn-custom-order:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 30px rgba(102, 126, 234, 0.4);
        color: white;
    }

    .custom-order-image img {
        transition: transform 0.5s ease;
    }

    .custom-order-image img:hover {
        transform: scale(1.02);
    }

    @media (max-width: 768px) {
        .custom-order-section {
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        .custom-order-card {
            padding: 1.5rem;
        }
    }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="page-header">
        <div class="container">
            <h1 class="mb-3">
                <?php echo $category ? $categories[$category]['name'] : 'All Products'; ?>
            </h1>            
            <p class="lead mb-0">Discover <?php echo $product_count; ?> handmade clothing items from our talented tailors</p>
        </div>
    </div>
   
    <!-- Category Navigation -->
    <div class="container">
        <div class="category-nav-v2">
            <div class="row g-3">
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="?" class="category-btn-v2 <?php echo !$category ? 'active' : ''; ?>">
                        <i class="fas fa-th"></i>
                        <div>All</div>
                        <span class="category-count-v2"><?php echo $total_products; ?></span>
                    </a>
                </div>
                <?php foreach ($categories as $key => $cat): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="?category=<?php echo $key; ?>" class="category-btn-v2 <?php echo $category == $key ? 'active' : ''; ?>">
                        <i class="fas fa-<?php echo $cat['icon']; ?>"></i>
                        <div><?php echo $cat['name']; ?></div>
                        <span class="category-count-v2"><?php echo $cat['count']; ?></span>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <div class="container py-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <!-- Stats -->
                <div class="stats-card-v2">
                    <i class="fas fa-chart-line"></i>
                    <h4>Shop Stats</h4>
                    <div class="stats-values-v2">
                        <div class="stat-item-v2">
                            <span class="stat-label">Total Products:</span>
                            <span class="stat-value"><?php echo $total_products; ?></span>
                        </div>
                        <div class="stat-item-v2">
                            <span class="stat-label">Avg Price:</span>
                            <span class="stat-value"><?php echo number_format($average_price_cfa, 0, '.', ','); ?> CFA</span>
                        </div>
                        <div class="stat-item-v2">
                            <span class="stat-label">Avg Rating:</span>
                            <span class="stat-value"><?php echo number_format($average_rating, 1); ?> / 5.0</span>
                        </div>
                    </div>
                </div>
                
                <!-- Price Filter -->
                <div class="price-filter-v2">
                    <h5><i class="fas fa-filter me-2"></i> Price Filter (CFA)</h5>
                    <div class="mt-3">
                        <input type="range" class="price-slider-v2" min="5000" max="500000" 
                               value="<?php echo $max_price ?: 250000; ?>" id="priceSlider">
                        <div class="price-values-v2">
                            <span>5,000 CFA</span>
                            <span id="currentPrice"><?php echo number_format($max_price ?: 250000, 0, '.', ','); ?> CFA</span>
                            <span>500,000 CFA</span>
                        </div>
                    </div>
                    <button class="btn-cart-v2 w-100 mt-3" onclick="applyPriceFilter()">
                        <i class="fas fa-check me-2"></i> Apply Filter
                    </button>
                </div>
                
                <!-- Sort Options -->
                <div class="sort-options-v2">
                    <h5><i class="fas fa-sort me-2"></i> Sort By</h5>
                    <ul class="sort-list-v2 mt-3">
                        <li class="sort-item-v2 <?php echo $sort == 'newest' ? 'active' : ''; ?>">
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'newest'])); ?>">
                                <i class="fas fa-clock me-2"></i> Newest First
                            </a>
                        </li>
                        <li class="sort-item-v2 <?php echo $sort == 'price_low' ? 'active' : ''; ?>">
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_low'])); ?>">
                                <i class="fas fa-arrow-up me-2"></i> Price: Low to High
                            </a>
                        </li>
                        <li class="sort-item-v2 <?php echo $sort == 'price_high' ? 'active' : ''; ?>">
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_high'])); ?>">
                                <i class="fas fa-arrow-down me-2"></i> Price: High to Low
                            </a>
                        </li>
                        <li class="sort-item-v2 <?php echo $sort == 'rating' ? 'active' : ''; ?>">
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'rating'])); ?>">
                                <i class="fas fa-star me-2"></i> Highest Rated
                            </a>
                        </li>
                    </ul>
                </div>


               

                <div class="price-filter-v2">
                    <h5><i class="fas fa-filter me-2"></i>Need a Perfect Fit?</h5>
                    <div class="mt-3">
                        <i class="fas fa-palette text-primary"></i>
                        <div>
                            <h5>Personalized Design</h5>
                            <p>Choose fabric, color, style, and customization options</p>
                            <p>Go to the the end of the page, or click directly below</p>
                        </div>
                    </div>
                    <a href="custom-order.php" class="btn-custom-order">
                        <i class="fas fa-scissors me-2"></i> Start Custom Order
                    </a>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <!-- Results Header -->
                <div class="results-header-v2">
                    <div>
                        <h3 class="mb-2"><?php echo $product_count; ?> Products Found</h3>
                        <?php if ($max_price): ?>
                            <p class="text-muted mb-0">Filtered by: Price ≤ <?php echo number_format($max_price, 0, '.', ','); ?> CFA</p>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="text-muted">Showing <?php echo $product_count; ?> items</span>
                        <a href="?" class="btn-view-v2">
                            <i class="fas fa-redo"></i> Clear All
                        </a>
                    </div>
                </div>
                
                <!-- Products Grid -->
                <?php if ($product_count > 0): ?>
                    <div class="products-grid-v2">
                        <?php foreach ($products as $index => $product): 
                            $cat = $product['category'];
                            $images = $clothingImages[$cat] ?? $clothingImages['traditional'];
                            $imageIndex = $index % count($images);
                            $imageUrl = $images[$imageIndex];
                            $categoryName = $categories[$cat]['name'] ?? ucfirst($cat);
                            $priceCFA = $product['price'] * 600; // Convert to CFA
                        ?>
                        <div class="product-card-v2" data-product-id="<?php echo $product['id']; ?>">
                            <div class="product-img-container-v2">
                                <img src="<?php echo $imageUrl; ?>" 
                                     alt="<?php echo htmlspecialchars($product['title']); ?>" 
                                     class="product-img-v2"
                                     loading="lazy">
                                <span class="product-badge-v2"><?php echo $categoryName; ?></span>
                            </div>
                            
                            <div class="product-info-v2">
                                <h3 class="product-title-v2"><?php echo htmlspecialchars($product['title']); ?></h3>
                                <p class="product-description-v2">
                                    <?php echo htmlspecialchars($product['description'] ?? 'No description available.'); ?>
                                </p>
                                
                                <div class="product-price-v2">
                                    <span class="price-main"><?php echo number_format($priceCFA, 0, '.', ','); ?> CFA</span>
                                    <?php if ($priceCFA > 50000): ?>
                                        <span class="price-secondary">$<?php echo number_format($product['price'], 2); ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="product-meta-v2">
                                    <div class="tailor-info-v2">
                                        <i class="fas fa-user-tie"></i>
                                        <span><?php echo htmlspecialchars($product['tailor_name']); ?></span>
                                    </div>
                                    <div class="rating-v2">
                                        <i class="fas fa-star"></i>
                                        <span><?php echo number_format($product['rating'], 1); ?></span>
                                    </div>
                                </div>
                                
                                <div class="product-actions-v2">
                                    <button class="btn-view-v2" onclick="viewProduct(<?php echo $product['id']; ?>)">
                                        <i class="fas fa-eye"></i> View Details
                                    </button>
                                    <button class="btn-cart-v2" onclick="addToCart(this, <?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['title']); ?>', <?php echo $priceCFA; ?>)">
                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state-v2">
                        <i class="fas fa-search"></i>
                        <h3>No products found</h3>
                        <p class="text-muted mb-4">Try adjusting your filters or selecting a different category</p>
                        <a href="?" class="btn-cart-v2" style="width: auto; padding: 0.75rem 2rem;">
                            <i class="fas fa-redo me-2"></i> Reset Filters
                        </a>
                    </div>
                <?php endif; ?>
                
                <!-- Quick Cart Notification -->
                <div id="cartNotification" class="cart-notification-v2">
                    <div class="notification-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div>
                        <strong id="cartProductName"></strong>
                        <div>Added to cart successfully!</div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Custom Order Section -->
        <div class="custom-order-section mt-5 pt-5">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="custom-order-card">
                        <h2 class="mb-4">Need a Perfect Fit?</h2>
                        <p class="lead mb-4">Get clothing tailored specifically for your body measurements by our expert tailors.</p>
                        
                        <div class="features-list mb-4">
                            <div class="feature-item">
                                <i class="fas fa-ruler-combined text-primary"></i>
                                <div>
                                    <h5>Custom Measurements</h5>
                                    <p>Provide your exact measurements for perfect fitting clothes</p>
                                </div>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-palette text-primary"></i>
                                <div>
                                    <h5>Personalized Design</h5>
                                    <p>Choose fabric, color, style, and customization options</p>
                                </div>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-user-tie text-primary"></i>
                                <div>
                                    <h5>Expert Tailoring</h5>
                                    <p>Handcrafted by our master tailors with attention to detail</p>
                                </div>
                            </div>
                        </div>
                        
                        <a href="custom-order.php" class="btn-custom-order">
                            <i class="fas fa-scissors me-2"></i> Start Custom Order
                        </a>
                        
                        <div class="mt-4">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i> 
                                Average delivery time: 7-14 days | Price varies by design
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="custom-order-image">
                        <img src="https://images.unsplash.com/photo-1515378791036-0648a3ef77b2?w=600&h=600&fit=crop" 
                            alt="Custom Tailoring" 
                            class="img-fluid rounded-3 shadow">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Initialize price slider
    const priceSlider = document.getElementById('priceSlider');
    const currentPrice = document.getElementById('currentPrice');
    
    function updatePriceSlider() {
        const value = priceSlider.value;
        const percent = ((value - 5000) / (500000 - 5000)) * 100;
        priceSlider.style.background = `linear-gradient(90deg, var(--primary) ${percent}%, #e2e8f0 ${percent}%)`;
        currentPrice.textContent = parseInt(value).toLocaleString('en-US') + ' CFA';
    }
    
    priceSlider.addEventListener('input', updatePriceSlider);
    updatePriceSlider(); // Initial update
    
    // Cart functionality
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    let cartTotal = parseFloat(localStorage.getItem('cartTotal')) || 0;
    
    function addToCart(button, productId, productName, productPriceCFA) {
        // Find product in sample data (in real app, you'd have this from database)
        const product = {
            id: productId,
            name: productName,
            price: productPriceCFA,
            quantity: 1,
            image: button.closest('.product-card-v2').querySelector('.product-img-v2').src
        };
        
        // Check if product already in cart
        const existingIndex = cart.findIndex(item => item.id === productId);
        
        if (existingIndex > -1) {
            cart[existingIndex].quantity += 1;
        } else {
            cart.push(product);
        }
        
        // Update cart total
        cartTotal += productPriceCFA;
        
        // Save to localStorage
        localStorage.setItem('cart', JSON.stringify(cart));
        localStorage.setItem('cartTotal', cartTotal.toFixed(2));
        
        // Update button state
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check"></i> Added!';
        button.disabled = true;
        button.style.background = '#10b981';
        
        // Show notification
        showCartNotification(productName);
        
        // Update cart count in header if exists
        updateCartCount();
        
        // Reset button after 2 seconds
        setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = false;
            button.style.background = '';
        }, 2000);
    }
    
    function showCartNotification(productName) {
        const notification = document.getElementById('cartNotification');
        document.getElementById('cartProductName').textContent = productName;
        
        notification.classList.add('show');
        
        setTimeout(() => {
            notification.classList.remove('show');
        }, 3000);
    }
    
    function updateCartCount() {
        // Update cart count in navbar if exists
        const cartCountElements = document.querySelectorAll('.cart-count');
        cartCountElements.forEach(element => {
            const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
            element.textContent = totalItems;
            element.style.display = totalItems > 0 ? 'inline-block' : 'none';
        });
    }
    
    function viewProduct(productId) {
        // In real app, redirect to product detail page
        alert('Viewing product #' + productId + '\nIn a real application, this would redirect to:\nproduct.php?id=' + productId);
        // window.location.href = 'product.php?id=' + productId;
    }
    
    function applyPriceFilter() {
        const maxPrice = priceSlider.value;
        const currentUrl = new URL(window.location.href);
        
        if (maxPrice < 500000) {
            currentUrl.searchParams.set('max_price', maxPrice);
        } else {
            currentUrl.searchParams.delete('max_price');
        }
        
        window.location.href = currentUrl.toString();
    }
    
    // Initialize cart count on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateCartCount();
        
        // Add hover effects to product cards
        document.querySelectorAll('.product-card-v2').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.boxShadow = '0 15px 35px rgba(102, 126, 234, 0.15)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = '';
                this.style.boxShadow = '';
            });
        });
        
        // Add click animation to category buttons
        document.querySelectorAll('.category-btn-v2').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.category-btn-v2').forEach(b => {
                    b.classList.remove('active');
                });
                this.classList.add('active');
            });
        });
        
        // Add animation to sort items
        document.querySelectorAll('.sort-item-v2').forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(5px)';
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.transform = '';
            });
        });
    });
    
    // Quick view modal (simplified version)
    function quickView(productId) {
        // In a real application, this would fetch product details via AJAX
        // and show them in a modal
        console.log('Quick view for product:', productId);
        
        // For now, just show an alert
        alert('Quick View feature would show product details here.\nProduct ID: ' + productId);
    }
    </script>
</body>
</html>


<?php
/*// PRODUCTS PAGE - COMPLETE VERSION
require_once '../../config.php';
require_once '../../includes/classes/Database.php';
// pages/products/index.php

$db = Database::getInstance();

// 1. You MUST define the query FIRST
$db->query("SELECT * FROM products ORDER BY created_at DESC"); 

// 2. THEN you can get the results
$products = $db->resultSet();

// Get query parameters
$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$max_price = $_GET['max_price'] ?? '';

// Sample clothing categories
$categories = [
    'traditional' => ['name' => 'Traditional Wear', 'icon' => 'tshirt', 'count' => 8],
    'modern' => ['name' => 'Modern Fashion', 'icon' => 'suitcase', 'count' => 7],
    'wedding' => ['name' => 'Wedding Attire', 'icon' => 'ring', 'count' => 6],
    'formal' => ['name' => 'Formal Wear', 'icon' => 'briefcase', 'count' => 4], // Add this
    'custom' => ['name' => 'Custom Designs', 'icon' => 'pencil-square', 'count' => 4], // Add this
    'casual' => ['name' => 'Casual Wear', 'icon' => 'tshirt', 'count' => 9],
    'accessories' => ['name' => 'Accessories', 'icon' => 'gem', 'count' => 5],
    'kids' => ['name' => 'Kids Wear', 'icon' => 'child', 'count' => 4]
];

// Comprehensive sample products data
$sampleProducts = [
    // Traditional Wear (8 items)
    ['id' => 1, 'category' => 'traditional', 'title' => 'African Print Kaftan', 'price' => 89.99, 'tailor_name' => 'Aisha Designs', 'rating' => 4.8, 'description' => 'Beautiful hand-printed kaftan with traditional African patterns, perfect for special occasions.'],
    ['id' => 2, 'category' => 'traditional', 'title' => 'Indian Silk Saree', 'price' => 149.99, 'tailor_name' => 'Bollywood Tailors', 'rating' => 4.9, 'description' => 'Premium silk saree with intricate embroidery and gold thread work.'],
    ['id' => 3, 'category' => 'traditional', 'title' => 'Japanese Kimono', 'price' => 199.99, 'tailor_name' => 'Tokyo Crafts', 'rating' => 4.7, 'description' => 'Authentic Japanese kimono made from premium silk fabric.'],
    ['id' => 4, 'category' => 'traditional', 'title' => 'Chinese Cheongsam', 'price' => 129.99, 'tailor_name' => 'Shanghai Silk', 'rating' => 4.6, 'description' => 'Elegant cheongsam dress with traditional Chinese embroidery.'],
    ['id' => 5, 'category' => 'traditional', 'title' => 'Arab Thobe', 'price' => 79.99, 'tailor_name' => 'Desert Tailors', 'rating' => 4.5, 'description' => 'Traditional Arab thobe made from lightweight cotton fabric.'],
    ['id' => 6, 'category' => 'traditional', 'title' => 'Korean Hanbok', 'price' => 169.99, 'tailor_name' => 'Seoul Fashion', 'rating' => 4.8, 'description' => 'Colorful Korean hanbok with vibrant colors and patterns.'],
    ['id' => 7, 'category' => 'traditional', 'title' => 'Scottish Kilt', 'price' => 189.99, 'tailor_name' => 'Highland Crafts', 'rating' => 4.7, 'description' => 'Authentic Scottish kilt with clan tartan pattern.'],
    ['id' => 8, 'category' => 'traditional', 'title' => 'Mexican Poncho', 'price' => 59.99, 'tailor_name' => 'Aztec Designs', 'rating' => 4.4, 'description' => 'Warm Mexican poncho with traditional geometric patterns.'],
    
    // Modern Fashion (7 items)
    ['id' => 9, 'category' => 'modern', 'title' => 'Designer Blazer', 'price' => 129.99, 'tailor_name' => 'Urban Stitch', 'rating' => 4.6, 'description' => 'Modern tailored blazer perfect for business meetings and formal events.'],
    ['id' => 10, 'category' => 'modern', 'title' => 'Leather Jacket', 'price' => 159.99, 'tailor_name' => 'Leather Masters', 'rating' => 4.5, 'description' => 'Handcrafted genuine leather jacket with premium finish.'],
    ['id' => 11, 'category' => 'modern', 'title' => 'Evening Gown', 'price' => 229.99, 'tailor_name' => 'Glamour Stitch', 'rating' => 4.9, 'description' => 'Elegant evening gown with sequin details and silk lining.'],
    ['id' => 12, 'category' => 'modern', 'title' => 'Designer Dress', 'price' => 99.99, 'tailor_name' => 'Fashion Hub', 'rating' => 4.4, 'description' => 'Contemporary designer dress with unique cut and pattern.'],
    ['id' => 13, 'category' => 'modern', 'title' => 'Tailored Suit', 'price' => 299.99, 'tailor_name' => 'Premium Tailors', 'rating' => 4.8, 'description' => 'Custom tailored three-piece suit for formal occasions.'],
    ['id' => 14, 'category' => 'modern', 'title' => 'Designer Jumpsuit', 'price' => 89.99, 'tailor_name' => 'Modern Wear', 'rating' => 4.3, 'description' => 'Trendy jumpsuit with elegant design and comfortable fit.'],
    ['id' => 15, 'category' => 'modern', 'title' => 'Linen Set', 'price' => 109.99, 'tailor_name' => 'Linen Studio', 'rating' => 4.6, 'description' => 'Premium linen shirt and pants set for summer wear.'],
    
    // FORMAL CATEGORY (African Formal Wear)
    ['id' => 40, 'category' => 'formal', 'title' => 'Premium Midnight Agbada', 'price' => 125000, 'tailor_name' => 'Lagos Luxe', 'rating' => 5.0, 'description' => 'Four-piece royal Agbada set with silver hand-embroidery.'],
    ['id' => 41, 'category' => 'formal', 'title' => 'Ivory Senator Suit', 'price' => 55000, 'tailor_name' => 'Dakar Designs', 'rating' => 4.8, 'description' => 'Polished Senator wear with a modern slim-fit cut and chest detail.'],
    ['id' => 42, 'category' => 'formal', 'title' => 'Silk Kaftan Evening Gown', 'price' => 85000, 'tailor_name' => 'Accra Chic', 'rating' => 4.9, 'description' => 'Elegant floor-length silk gown featuring traditional gold thread patterns.'],
    ['id' => 43, 'category' => 'formal', 'title' => 'Embroidered Brocade Suit', 'price' => 140000, 'tailor_name' => 'Royal Stitches', 'rating' => 4.7, 'description' => 'Luxury brocade fabric tailored into a formal high-collar suit.'],
    ['id' => 43, 'category' => 'formal', 'title' => 'Couple Suit', 'price' => 300000, 'tailor_name' => 'Royal Stitches', 'rating' => 4.7, 'description' => 'Luxury brocade fabric tailored into a formal high-collar suit.'],


    // CUSTOM CATEGORY (Bespoke/Made-to-Measure)
    ['id' => 45, 'category' => 'custom', 'title' => 'Bespoke Corporate Set', 'price' => 175000, 'tailor_name' => 'Master Stitch', 'rating' => 5.0, 'description' => 'Fully customized 3-piece suit tailored to your exact body measurements.'],
    ['id' => 46, 'category' => 'custom', 'title' => 'Hand-Painted Silk Kaftan', 'price' => 65000, 'tailor_name' => 'Artisanal Wear', 'rating' => 4.9, 'description' => 'Unique hand-painted batik patterns on premium heavy silk.'],
    ['id' => 47, 'category' => 'custom', 'title' => 'Custom Kente Wedding Gown', 'price' => 250000, 'tailor_name' => 'Heritage Couture', 'rating' => 5.0, 'description' => 'Hand-woven Kente fabric designed into a custom bridal masterpiece.'],
    ['id' => 48, 'category' => 'custom', 'title' => 'Tailored Dashiki Blazer', 'price' => 45000, 'tailor_name' => 'Urban African', 'rating' => 4.6, 'description' => 'A custom-fit blazer merging modern business cuts with Dashiki prints.'],

    // Wedding Attire (6 items)
    ['id' => 16, 'category' => 'wedding', 'title' => 'Bridal Wedding Dress', 'price' => 499.99, 'tailor_name' => 'Bridal Couture', 'rating' => 5.0, 'description' => 'Custom made wedding dress with lace details and train.'],
    ['id' => 17, 'category' => 'wedding', 'title' => 'Groom Suit', 'price' => 299.99, 'tailor_name' => 'Gentleman Tailors', 'rating' => 4.8, 'description' => 'Tailored three-piece wedding suit with silk lining.'],
    ['id' => 18, 'category' => 'wedding', 'title' => 'Bridesmaid Dress', 'price' => 129.99, 'tailor_name' => 'Party Style', 'rating' => 4.7, 'description' => 'Beautiful bridesmaid dress available in various colors.'],
    ['id' => 19, 'category' => 'wedding', 'title' => 'Mother of Bride Dress', 'price' => 179.99, 'tailor_name' => 'Elegant Wear', 'rating' => 4.6, 'description' => 'Elegant dress perfect for mother of the bride.'],
    ['id' => 20, 'category' => 'wedding', 'title' => 'Flower Girl Dress', 'price' => 69.99, 'tailor_name' => 'Little Princess', 'rating' => 4.9, 'description' => 'Adorable flower girl dress with lace details.'],
    ['id' => 21, 'category' => 'wedding', 'title' => 'Groomsmen Set', 'price' => 199.99, 'tailor_name' => 'Formal Wear', 'rating' => 4.5, 'description' => 'Complete groomsmen suit set for wedding party.'],
    
    // Casual Wear (9 items)
    ['id' => 22, 'category' => 'casual', 'title' => 'Denim Jeans', 'price' => 59.99, 'tailor_name' => 'Denim Factory', 'rating' => 4.4, 'description' => 'Custom fit denim jeans with premium fabric.'],
    ['id' => 23, 'category' => 'casual', 'title' => 'Cotton T-Shirt', 'price' => 24.99, 'tailor_name' => 'Comfort Wear', 'rating' => 4.3, 'description' => 'Premium cotton t-shirt with custom print options.'],
    ['id' => 24, 'category' => 'casual', 'title' => 'Summer Dress', 'price' => 49.99, 'tailor_name' => 'Sunny Designs', 'rating' => 4.6, 'description' => 'Light summer dress with floral pattern and comfortable fit.'],
    ['id' => 25, 'category' => 'casual', 'title' => 'Hoodie', 'price' => 44.99, 'tailor_name' => 'Cozy Wear', 'rating' => 4.5, 'description' => 'Comfortable hoodie with front pocket and adjustable hood.'],
    ['id' => 26, 'category' => 'casual', 'title' => 'Cargo Pants', 'price' => 54.99, 'tailor_name' => 'Urban Comfort', 'rating' => 4.4, 'description' => 'Practical cargo pants with multiple pockets.'],
    ['id' => 27, 'category' => 'casual', 'title' => 'Polo Shirt', 'price' => 34.99, 'tailor_name' => 'Sport Style', 'rating' => 4.3, 'description' => 'Classic polo shirt perfect for casual occasions.'],
    ['id' => 28, 'category' => 'casual', 'title' => 'Sweatpants', 'price' => 39.99, 'tailor_name' => 'Relax Wear', 'rating' => 4.5, 'description' => 'Comfortable sweatpants for lounging and casual wear.'],
    ['id' => 29, 'category' => 'casual', 'title' => 'Shorts', 'price' => 29.99, 'tailor_name' => 'Summer Style', 'rating' => 4.4, 'description' => 'Comfortable shorts perfect for summer weather.'],
    ['id' => 30, 'category' => 'casual', 'title' => 'Cardigan', 'price' => 49.99, 'tailor_name' => 'Cozy Knits', 'rating' => 4.6, 'description' => 'Lightweight cardigan perfect for layering.'],
    
    // Accessories (5 items)
    ['id' => 31, 'category' => 'accessories', 'title' => 'Leather Handbag', 'price' => 89.99, 'tailor_name' => 'Leather Craft', 'rating' => 4.7, 'description' => 'Handmade leather handbag with multiple compartments.'],
    ['id' => 32, 'category' => 'accessories', 'title' => 'Silk Scarf', 'price' => 34.99, 'tailor_name' => 'Silk Studio', 'rating' => 4.5, 'description' => 'Printed silk scarf with vibrant patterns.'],
    ['id' => 33, 'category' => 'accessories', 'title' => 'Designer Belt', 'price' => 44.99, 'tailor_name' => 'Accessory Hub', 'rating' => 4.4, 'description' => 'Handcrafted leather belt with metal buckle.'],
    ['id' => 34, 'category' => 'accessories', 'title' => 'Wool Hat', 'price' => 29.99, 'tailor_name' => 'Winter Wear', 'rating' => 4.3, 'description' => 'Warm wool hat for winter season.'],
    ['id' => 35, 'category' => 'accessories', 'title' => 'Leather Wallet', 'price' => 39.99, 'tailor_name' => 'Leather Goods', 'rating' => 4.6, 'description' => 'Genuine leather wallet with multiple card slots.'],
    
    // Kids Wear (4 items)
    ['id' => 36, 'category' => 'kids', 'title' => 'Kids Traditional Set', 'price' => 39.99, 'tailor_name' => 'Kids Fashion', 'rating' => 4.8, 'description' => 'Traditional outfit set for children with matching pieces.'],
    ['id' => 37, 'category' => 'kids', 'title' => 'Baby Romper', 'price' => 19.99, 'tailor_name' => 'Baby Comfort', 'rating' => 4.6, 'description' => 'Comfortable baby romper with snap buttons.'],
    ['id' => 38, 'category' => 'kids', 'title' => 'Children Jacket', 'price' => 34.99, 'tailor_name' => 'Kids Warm', 'rating' => 4.5, 'description' => 'Warm jacket for children with hood.'],
    ['id' => 39, 'category' => 'kids', 'title' => 'Kids Jeans', 'price' => 24.99, 'tailor_name' => 'Little Denim', 'rating' => 4.4, 'description' => 'Durable jeans for active children.']
];

// Clothing images for each category
$clothingImages = [
    'traditional' => [
        'https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=400&h=500&fit=crop',
        'https://images.unsplash.com/photo-1572804013309-59a88b7e92f1?w=400&h=500&fit=crop',
        'https://images.unsplash.com/photo-1525459819821-1bb0b2b60d8e?w=400&h=500&fit=crop',
        'https://images.unsplash.com/photo-1577219491237-45c96647e15e?w=400&h=500&fit=crop',
        'https://images.unsplash.com/photo-1574201635302-388dd92c4d3a?w=400&h=500&fit=crop'
    ],
    'modern' => [
        'https://images.unsplash.com/photo-1523381210434-271e8be1f52b?w=400&h=500&fit=crop',
        'https://images.unsplash.com/photo-1539109136881-3be0616acf4b?w=400&h=500&fit=crop',
        'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=400&h=500&fit=crop',
        'https://images.unsplash.com/photo-1496747611176-843222e1e57c?w=400&h=500&fit=crop',
        'https://images.unsplash.com/photo-1552374196-c4e7ffc6e126?w=400&h=500&fit=crop'
    ],

    'formal'=> [
        // Men's Polished Senator Suit
        'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTNA-BrhdLtyPMog2YDzb0q7dw2WYSNM9-gEA&s',
        // Women's Elegant African Evening Gown
        'https://otunbastore.com/cdn/shop/products/il_fullxfull.5295786566_n7me.jpg?v=1703837852',
        // Men's Modern Formal Kaftan
        'https://africablooms.com/wp-content/uploads/2019/05/African-Clothing-for-Boys-Blue-Dashiki-for-Boys-Agbada-AFRICA-BLOOMS.jpg',
        // Women's Premium Lace/Ankara Formal Wear
        'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRalbkBz9VGB1wNBy6PwNxN2Zq7Z54qjn1rag&s',

        'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSjCsjrIqBP1FXTuYYgGsyFkH71oOC6Ei0GUw&s'
        // Men's Luxury Embroidered Agbada
    ],

    'custom' => [
            'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS4DA8F2FRQJ-KIxZEtIvU2yWehgPtM5KVH4w&s',
            'https://images.unsplash.com/photo-1511285560929-80b456fea0bc?w=400&h=500&fit=crop',
            'https://images.unsplash.com/photo-1511895426328-dc8714191300?w=400&h=500&fit=crop',
            'https://images.unsplash.com/photo-1539635278303-d4002c07eae3?w=400&h=500&fit=crop',
            'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?w=400&h=500&fit=crop'
        ],
    'wedding' => [
        'https://images.unsplash.com/photo-1519657337289-0776534cd2c7?w=400&h=500&fit=crop',
        'https://images.unsplash.com/photo-1511285560929-80b456fea0bc?w=400&h=500&fit=crop',
        'https://images.unsplash.com/photo-1511895426328-dc8714191300?w=400&h=500&fit=crop',
        'https://images.unsplash.com/photo-1539635278303-d4002c07eae3?w=400&h=500&fit=crop',
        'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?w=400&h=500&fit=crop'
    ],
    'casual' => [
        'https://images.unsplash.com/photo-1552374196-c4e7ffc6e126?w=400&h=500&fit=crop',
        'https://images.unsplash.com/photo-1505022610485-0249ba5b3675?w=400&h=500&fit=crop',
        'https://images.unsplash.com/photo-1558769132-cb1a40ed0ada?w=400&h=500&fit=crop',
        'https://images.unsplash.com/photo-1539008835657-9e8e9680c956?w=400&h=500&fit=crop',
        'https://images.unsplash.com/photo-1544441893-675973e31985?w=400&h=500&fit=crop'
    ],
    'accessories' => [
        'https://images.unsplash.com/photo-1584917865442-de89df76afd3?w=400&h=500&fit=crop',
        'https://images.unsplash.com/photo-1591561954557-26941169b49e?w=400&h=500&fit=crop',
        'https://images.unsplash.com/photo-1543163521-1bf539c55dd2?w=400&h=500&fit=crop',
        'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=400&h=500&fit=crop',
        'https://images.unsplash.com/photo-1584917865442-de89df76afd3?w=400&h=500&fit=crop'
    ],
    'kids' => [
        'https://images.unsplash.com/photo-1561489409-e420d0f26b43?w=400&h=500&fit=crop',
        'https://images.unsplash.com/photo-1558769132-92e717d613cd?w=400&h=500&fit=crop',
        'https://images.unsplash.com/photo-1552902865-b72c031ac5ea?w=400&h=500&fit=crop',
        'https://images.unsplash.com/photo-1539008835657-9e8e9680c956?w=400&h=500&fit=crop',
        'https://images.unsplash.com/photo-1515378791036-0648a3ef77b2?w=400&h=500&fit=crop'
    ]
];

// Process products
try {
    $sql = "SELECT p.*, u.username as tailor_name FROM products p 
            LEFT JOIN users u ON p.tailor_id = u.id 
            WHERE p.status = 'active'";
    
    $params = [];
    
    if ($category) {
        $sql .= " AND p.category = ?";
        $params[] = $category;
    }
    
    if ($max_price) {
        $sql .= " AND p.price <= ?";
        $params[] = $max_price;
    }
    
    // Sorting
    $sortMap = [
        'price_low' => 'p.price ASC',
        'price_high' => 'p.price DESC', 
        'rating' => 'p.rating DESC',
        'popular' => 'p.view_count DESC'
    ];
    $sql .= " ORDER BY " . ($sortMap[$sort] ?? 'p.created_at DESC');
    
    $db = Database::getInstance();
    $db->query($sql);
    foreach ($params as $i => $param) {
        $db->bind(":param" . ($i + 1), $param);
    }
    $products = $db->fetchAll();
    
    // If no products in database, use sample data
    if (empty($products)) {
        $products = array_filter($sampleProducts, function($product) use ($category, $max_price) {
            $categoryMatch = !$category || $product['category'] === $category;
            $priceMatch = !$max_price || $product['price'] <= $max_price;
            return $categoryMatch && $priceMatch;
        });
        
        // Apply sorting to sample data
        if ($sort === 'price_low') {
            usort($products, fn($a, $b) => $a['price'] <=> $b['price']);
        } elseif ($sort === 'price_high') {
            usort($products, fn($a, $b) => $b['price'] <=> $a['price']);
        } elseif ($sort === 'rating') {
            usort($products, fn($a, $b) => $b['rating'] <=> $a['rating']);
        }
    }
    
    $product_count = count($products);
    
    // Calculate category counts
    foreach ($categories as $cat => &$catInfo) {
        $catInfo['count'] = count(array_filter($sampleProducts, fn($p) => $p['category'] === $cat));
    }
    
} catch (Exception $e) {
    // Use sample data if database fails
    $products = array_filter($sampleProducts, function($product) use ($category, $max_price) {
        $categoryMatch = !$category || $product['category'] === $category;
        $priceMatch = !$max_price || $product['price'] <= $max_price;
        return $categoryMatch && $priceMatch;
    });
    $product_count = count($products);
}

// Calculate stats
$total_products = count($sampleProducts);
$average_price = array_sum(array_column($sampleProducts, 'price')) / $total_products;
$average_rating = array_sum(array_column($sampleProducts, 'rating')) / $total_products;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $category ? $categories[$category]['name'] : 'All'; ?> Products - Tailor Shop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    :root {
        --primary: #667eea;
        --secondary: #764ba2;
    }
    
    body {
        background: #f8fafc;
        font-family: 'Segoe UI', system-ui, sans-serif;
    }
    
    .page-header {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        padding: 3rem 0;
        position: relative;
        overflow: hidden;
    }
    
    .page-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.1' fill-rule='evenodd'/%3E%3C/svg%3E");
        opacity: 0.3;
    }
    
    .category-nav {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        margin: -2rem auto 2rem;
        max-width: 1200px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        position: relative;
        z-index: 10;
    }
    
    .category-btn {
        padding: 1rem 1.5rem;
        border-radius: 12px;
        background: #f8fafc;
        color: #475569;
        font-weight: 500;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s;
        border: 2px solid transparent;
    }
    
    .category-btn:hover, .category-btn.active {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        border-color: white;
    }
    
    .category-count {
        background: rgba(255,255,255,0.2);
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 0.8rem;
    }
    
    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 25px;
        margin: 2rem 0;
    }
    
    .product-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        position: relative;
    }
    
    .product-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }
    
    .product-img-container {
        height: 250px;
        overflow: hidden;
        position: relative;
    }
    
    .product-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    
    .product-card:hover .product-img {
        transform: scale(1.1);
    }
    
    .product-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        padding: 6px 15px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        z-index: 2;
    }
    
    .product-info {
        padding: 1.5rem;
    }
    Cardigan
    .product-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.5rem;
        line-height: 1.4;
    }
    
    .product-description {
        color: #64748b;
        font-size: 0.9rem;
        margin-bottom: 1rem;
        line-height: 1.5;
    }
    
    .product-price {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary);
        margin: 1rem 0;
    }
    
    .product-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 1rem;
        border-top: 1px solid #e2e8f0;
    }
    
    .tailor-info {
        font-size: 0.9rem;
        color: #64748b;
    }
    
    .rating {
        color: #f59e0b;
        font-size: 0.9rem;
    }
    
    .product-actions {
        display: flex;
        gap: 10px;
        margin-top: 1rem;
    }
    
    .btn-view, .btn-cart {
        flex: 1;
        padding: 0.75rem;
        border-radius: 10px;
        font-weight: 500;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .btn-view {
        background: white;
        color: var(--primary);
        border: 2px solid var(--primary);
    }
    
    .btn-view:hover {
        background: var(--primary);
        color: white;
        transform: translateY(-2px);
    }
    
    .btn-cart {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
    }
    
    .btn-cart:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }
    
    .stats-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        margin-bottom: 1.5rem;
        border: 1px solid #e2e8f0;
    }
    
    .stats-card i {
        font-size: 2.5rem;
        color: var(--primary);
        margin-bottom: 1rem;
    }
    
    .price-slider {
        width: 100%;
        height: 8px;
        border-radius: 4px;
        background: #e2e8f0;
        outline: none;
        -webkit-appearance: none;
    }
    
    .price-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: var(--primary);
        cursor: pointer;
        border: 3px solid white;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    }
    
    .price-values {
        display: flex;
        justify-content: space-between;
        margin-top: 10px;
        color: #64748b;
        font-size: 0.9rem;
    }
    
    .cart-notification {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: white;
        border-radius: 10px;
        padding: 1rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        z-index: 1000;
        display: flex;
        align-items: center;
        gap: 10px;
        transform: translateY(100px);
        opacity: 0;
        transition: all 0.3s ease;
    }
    
    .cart-notification.show {
        transform: translateY(0);
        opacity: 1;
    }
    
    @media (max-width: 768px) {
        .products-grid {
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
Silk Kaftan Evening Gown         
        .category-nav {
            margin: 1rem 0 2rem;
            padding: 1rem;
        }
        
        .category-btn {
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
        }
    }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="page-header">
        <div class="container">
        <h1 class="mb-3">
            <?php echo $category ? ($categories[$category]['name'] ?? 'Product Category') : 'All Products'; ?>
        </h1>            
        <p class="lead mb-0">Discover <?php echo $product_count; ?> handmade clothing items from our talented tailors</p>
        </div>
    </div>
   
    <!-- Category Navigation -->
    <div class="container">
        <div class="category-nav">
            <div class="row g-3">
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="?" class="category-btn <?php echo !$category ? 'active' : ''; ?>">
                        <i class="fas fa-th"></i>
                        <div>
                            <div>All</div>
                            <small class="category-count"><?php echo $total_products; ?></small>
                        </div>
                    </a>
                </div>
                <?php foreach ($categories as $key => $cat): ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="?category=<?php echo $key; ?>" class="category-btn <?php echo $category == $key ? 'active' : ''; ?>">
                        <i class="fas fa-<?php echo $cat['icon']; ?>"></i>
                        <div>
                            <div><?php echo $cat['name']; ?></div>
                            <small class="category-count"><?php echo $cat['count']; ?></small>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <div class="container py-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <!-- Stats -->
                <div class="stats-card">
                    <i class="fas fa-chart-line"></i>
                    <h4>Shop Stats</h4>
                    <div class="text-start mt-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Products:</span>
                            <strong><?php echo $total_products; ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Avg Price:</span>
                            <strong>$<?php echo number_format($average_price, 2); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Avg Rating:</span>
                            <strong><?php echo number_format($average_rating, 1); ?>/5</strong>
                        </div>
                    </div>
                </div>
                
                <!-- Price Filter -->
                <div class="stats-card">
                    <h5><i class="fas fa-filter me-2"></i> Price Range</h5>
                    <div class="mt-3">
                        <input type="range" class="price-slider" min="0" max="1000" value="<?php echo $max_price ?: 500; ?>" id="priceSlider">
                        <div class="price-values">
                            <span>$0</span>
                            <span id="currentPrice">$<?php echo $max_price ?: 500; ?></span>
                            <span>$1000</span>
                        </div>
                    </div>
                    <button class="btn-cart w-100 mt-3" onclick="applyPriceFilter()">
                        <i class="fas fa-check me-2"></i> Apply Filter
                    </button>
                </div>
                
                <!-- Sort Options -->
                <div class="stats-card">
                    <h5><i class="fas fa-sort me-2"></i> Sort By</h5>
                    <div class="list-group list-group-flush mt-3">
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'newest'])); ?>" 
                           class="list-group-item list-group-item-action border-0 py-2 <?php echo $sort == 'newest' ? 'active' : ''; ?>">
                            <i class="fas fa-clock me-2"></i> Newest First
                        </a>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_low'])); ?>" 
                           class="list-group-item list-group-item-action border-0 py-2 <?php echo $sort == 'price_low' ? 'active' : ''; ?>">
                            <i class="fas fa-arrow-up me-2"></i> Price: Low to High
                        </a>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_high'])); ?>" 
                           class="list-group-item list-group-item-action border-0 py-2 <?php echo $sort == 'price_high' ? 'active' : ''; ?>">
                            <i class="fas fa-arrow-down me-2"></i> Price: High to Low
                        </a>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'rating'])); ?>" 
                           class="list-group-item list-group-item-action border-0 py-2 <?php echo $sort == 'rating' ? 'active' : ''; ?>">
                            <i class="fas fa-star me-2"></i> Highest Rated
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <!-- Results Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="mb-2"><?php echo $product_count; ?> Products Found</h3>
                        <?php if ($max_price): ?>
                            <p class="text-muted mb-0">Filtered by: Price ≤ $<?php echo $max_price; ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="text-muted">Showing <?php echo $product_count; ?> items</span>
                        <a href="?" class="btn-view">
                            <i class="fas fa-redo"></i> Clear All
                        </a>
                    </div>
                </div>
                
                <!-- Products Grid -->
                <?php if ($product_count > 0): ?>
                    <div class="products-grid">
                        <?php foreach ($products as $index => $product): 
                            $cat = $product['category'];
                            $images = $clothingImages[$cat] ?? $clothingImages['traditional'];
                            $imageIndex = $index % count($images);
                            $imageUrl = $images[$imageIndex];
                            $categoryName = $categories[$cat]['name'] ?? ucfirst($cat);
                        ?>
                        <div class="product-card" data-product-id="<?php echo $product['id']; ?>">
                            <div class="product-img-container">
                                <img src="<?php echo $imageUrl; ?>" 
                                     alt="<?php echo htmlspecialchars($product['title']); ?>" 
                                     class="product-img"
                                     loading="lazy">
                                <<span class="price"><?php echo number_format($product['price'] ?? 0); ?> CFA</span>
                            </div>
                            
                            <div class="product-info">
                                <h3 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h3>
                                <p class="product-description">
                                    <?php echo htmlspecialchars($product['description'] ?? 'No description available.'); ?>
                                </p>
                                
                                <div class="product-price">
                                    $<?php echo number_format($product['price'], 2); ?>
                                </div>
                                
                                <div class="product-meta">
                                    <div class="tailor-info">
                                        <i class="fas fa-user-tie me-1"></i>
                                        <?php echo htmlspecialchars($product['tailor_name']); ?>
                                    </div>
                                    <div class="rating">
                                        <i class="fas fa-star"></i>
                                        <?php echo number_format($product['rating'], 1); ?>
                                    </div>
                                </div>
                                
                                <div class="product-actions">
                                    <button class="btn-view" onclick="viewProduct(<?php echo $product['id']; ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <button class="btn-cart" onclick="addToCart(this, <?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['title']); ?>', <?php echo $product['price']; ?>)">
                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 my-5">
                        <i class="fas fa-search fa-4x text-muted mb-4"></i>
                        <h3>No products found</h3>
                        <p class="text-muted mb-4">Try adjusting your filters or selecting a different category</p>
                        <a href="?" class="btn-cart" style="width: auto; padding: 0.75rem 2rem;">
                            <i class="fas fa-redo me-2"></i> Reset Filters
                        </a>
                    </div>
                <?php endif; ?>
                
                <!-- Quick Cart Notification -->
                <div id="cartNotification" class="cart-notification">
                    <i class="fas fa-check-circle text-success fa-2x"></i>
                    <div>
                        <strong id="cartProductName"></strong>
                        <div>Added to cart successfully!</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Initialize price slider
    const priceSlider = document.getElementById('priceSlider');
    const currentPrice = document.getElementById('currentPrice');
    
    function updatePriceSlider() {
        const value = priceSlider.value;
        const percent = (value / priceSlider.max) * 100;
        priceSlider.style.background = `linear-gradient(90deg, var(--primary) ${percent}%, #e2e8f0 ${percent}%)`;
        currentPrice.textContent = '$' + value;
    }
    
    priceSlider.addEventListener('input', updatePriceSlider);
    updatePriceSlider(); // Initial update
    
    // Cart functionality
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    let cartTotal = parseFloat(localStorage.getItem('cartTotal')) || 0;
    
    function addToCart(button, productId, productName, productPrice) {
        // Find product in sample data (in real app, you'd have this from database)
        const product = {
            id: productId,
            name: productName,
            price: productPrice,
            quantity: 1,
            image: button.closest('.product-card').querySelector('.product-img').src
        };
        
        // Check if product already in cart
        const existingIndex = cart.findIndex(item => item.id === productId);
        
        if (existingIndex > -1) {
            cart[existingIndex].quantity += 1;
        } else {
            cart.push(product);
        }
        
        // Update cart total
        cartTotal += productPrice;
        
        // Save to localStorage
        localStorage.setItem('cart', JSON.stringify(cart));
        localStorage.setItem('cartTotal', cartTotal.toFixed(2));
        
        // Update button state
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check"></i> Added!';
        button.disabled = true;
        button.style.background = '#10b981';
        
        // Show notification
        showCartNotification(productName);
        
        // Update cart count in header if exists
        updateCartCount();
        
        // Reset button after 2 seconds
        setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = false;
            button.style.background = '';
        }, 2000);
    }
    
    function showCartNotification(productName) {
        const notification = document.getElementById('cartNotification');
        document.getElementById('cartProductName').textContent = productName;
        
        notification.classList.add('show');
        
        setTimeout(() => {
            notification.classList.remove('show');
        }, 3000);
    }
    
    function updateCartCount() {
        // Update cart count in navbar if exists
        const cartCountElements = document.querySelectorAll('.cart-count');
        cartCountElements.forEach(element => {
            const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
            element.textContent = totalItems;
            element.style.display = totalItems > 0 ? 'inline-block' : 'none';
        });
    }
    
    function viewProduct(productId) {
        // In real app, redirect to product detail page
        alert('Viewing product #' + productId + '\nIn a real application, this would redirect to:\nproduct.php?id=' + productId);
        // window.location.href = 'product.php?id=' + productId;
    }
    
    function applyPriceFilter() {
        const maxPrice = priceSlider.value;
        const currentUrl = new URL(window.location.href);
        
        if (maxPrice < 1000) {
            currentUrl.searchParams.set('max_price', maxPrice);
        } else {
            currentUrl.searchParams.delete('max_price');
        }
        
        window.location.href = currentUrl.toString();
    }
    
    // Initialize cart count on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateCartCount();
        
        // Add hover effects to product cards
        document.querySelectorAll('.product-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px)';
                this.style.boxShadow = '0 20px 40px rgba(0,0,0,0.15)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = '';
                this.style.boxShadow = '';
            });
        });
        
        // Add click animation to category buttons
        document.querySelectorAll('.category-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.category-btn').forEach(b => {
                    b.classList.remove('active');
                });
                this.classList.add('active');
            });
        });
    });
    
    // Quick view modal (simplified version)
    function quickView(productId) {
        // In a real application, this would fetch product details via AJAX
        // and show them in a modal
        console.log('Quick view for product:', productId);
        
        // For now, just show an alert
        alert('Quick View feature would show product details here.\nProduct ID: ' + productId);
    }
    </script>
</body>
</html>
*/

