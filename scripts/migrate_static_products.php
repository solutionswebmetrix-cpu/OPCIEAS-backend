<?php
require_once __DIR__ . '/../config/config.php';

$assetRoot = realpath(__DIR__ . '/../../frontend/src/assets/product');
$uploadDir = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . 'products';
$uploadUrlPrefix = UPLOAD_URL . 'products/';

if (!$assetRoot || !is_dir($assetRoot)) {
    fwrite(STDERR, "ERROR: Asset root not found: {$assetRoot}\n");
    exit(1);
}

if (!is_dir($uploadDir)) {
    if (!@mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        fwrite(STDERR, "ERROR: Cannot create uploads directory: {$uploadDir}\n");
        exit(1);
    }
}

$htaccess = $uploadDir . '/.htaccess';
if (!file_exists($htaccess)) {
    @file_put_contents($htaccess, "Options -Indexes\nAllow from all\n");
}
$indexHtml = $uploadDir . '/index.html';
if (!file_exists($indexHtml)) {
    @file_put_contents($indexHtml, '');
}

$STRICT_RAW_NAME_OVERRIDE = [
    'Adjustable Height Table' => 'Office Furniture',
    'Adjustable Wood-Top Workshop Table' => 'Office Furniture',
    'Beige Seat Tripod Workshop Stool' => 'Office Furniture',
    'Bisleri Counter Corner' => 'Office Furniture',
    'Black Furniture in a Minimal Classroom' => 'School Furniture',
    'Black Office Pedestal with Key' => 'Office Furniture',
    'Blank Classroom Notice Board' => 'Educational Furniture',
    'Blue Backdrop School Desk Chair' => 'School Furniture',
    'Blue Chair in a Workshop Setting' => 'Industrial Storage',
    'Blue Corner Study Desk Set' => 'School Furniture',
    'Blue Desk Training Room' => 'Educational Furniture',
    'Blue Lecture Chair with Writing Tablet' => 'Educational Furniture',
    'Blue Pipe Fitting in Workshop' => 'Industrial Storage',
    'Blue Work Jacket Inside Open Steel Locker' => 'Industrial Storage',
    'Blue and White Two-Tier Step Bench' => 'School Furniture',
    'Blue and White Worksite Benches' => 'Industrial Storage',
    'Bright Twin Slide Playground' => 'School Furniture',
    'Burgundy Classroom Chair with Writing Tablet' => 'School Furniture',
    'Chrome Three-Seat Waiting Bench' => 'Office Furniture',
    'Chrome Wire Shelving on Terracotta Floor' => 'Industrial Storage',
    'Colorful Kindergarten Classroom Furniture' => 'School Furniture',
    'Colorful Outdoor Student Desk and Bench' => 'School Furniture',
    'Colour-blocked playground swings' => 'School Furniture',
    'Colourful Benches in Workshop Storage' => 'Industrial Storage',
    "Colourful Children's Table and Chair Set" => 'School Furniture',
    'Colourful Multishelf Cabinet Against Blue' => 'School Furniture',
    'Colourful Outdoor School Desk Set' => 'School Furniture',
    'Colourful Stools on Blue Background' => 'School Furniture',
    'Colourful Tiered Shelf Against Patterned Tapestry' => 'Educational Furniture',
    'Cream Storage Cabinet with Open Doors' => 'Hostel Furniture',
    'Cream Utility Hopper on Casters' => 'Industrial Storage',
    'Cream Workshop Cabinet with Open Doors' => 'Industrial Storage',
    'Decorative Tile Plumbing Fixture' => 'Bathroom Collection',
    'Empty wooden shelf against blue tarp' => 'Industrial Storage',
    'Floral Cups on a Pink Classroom Desk' => 'School Furniture',
    'Gray Adjustable Drafting Table' => 'Office Furniture',
    'Green Stool on Outdoor Ground' => 'School Furniture',
    'Green and White Outdoor Student Desk' => 'School Furniture',
    'Industrial Cafeteria Table and Stools' => 'Industrial Storage',
    'Ivory Tusk Display Cabinet Decor' => 'Office Furniture',
    'Lavender Wall with Minimal Pedestal Table' => 'Office Furniture',
    'Light Wood Office Desk Set' => 'Office Furniture',
    'Light-Wood Modular Workstation Cabinet' => 'Office Furniture',
    'Lightwood Rolling Utility Cart' => 'Office Furniture',
    'Maroon Tablet-Arm Chair on Concrete' => 'Educational Furniture',
    'Metal Frame Bed in a Minimal Room' => 'Hostel Furniture',
    'Metal Storage Cabinet Interior' => 'Office Furniture',
    'Minimal Desk and Bench Set' => 'School Furniture',
    'Minimal Mint Corner with Table and Chair' => 'School Furniture',
    'Minimalist Desk and Chair Corner' => 'Office Furniture',
    'Minimalist Table and Chair on Blue Backdrop' => 'School Furniture',
    'Minimalist Taupe Metal Locker' => 'Hostel Furniture',
    'Mobile Beechwood Storage Cabinet' => 'Hostel Furniture',
    'Mobile filing cabinet with key lock' => 'Office Furniture',
    'Modern Beige Desk with Black Trim' => 'Office Furniture',
    'Modern Table with Colourful Chairs' => 'School Furniture',
    'Modern White-Tier Shelving Unit' => 'Industrial Storage',
    'Modern Wooden Cubicles in Office Lab' => 'Office Furniture',
    'Multicolour Children’s Table Set' => 'School Furniture',
    'Multicoloured Playground Against a Weathered Wall' => 'School Furniture',
    'Naval blue wooden desk on tiled floor' => 'School Furniture',
    'Nine-Compartment Steel Locker Cabinet' => 'Industrial Storage',
    'Open Wardrobe by the Pillar' => 'Hostel Furniture',
    'Open White Key Cabinet on Blue Tabletop' => 'Office Furniture',
    'Orange Desk and Bench Set' => 'School Furniture',
    'Orange Motorcycle Graphic Chair' => 'School Furniture',
    'Orange and Yellow Outdoor School Desk Set' => 'School Furniture',
    'Painted Rainbow Cubby Shelf Against Pink Wall' => 'School Furniture',
    'Pastel Study Nook in Mint Corner' => 'Hostel Furniture',
    'Peach and White Study Set in Mint Room' => 'Hostel Furniture',
    'Red Basketball Hoop in a Schoolyard' => 'School Furniture',
    'Red Chair with Writing Tablet' => 'School Furniture',
    'Red Heart Mirror and Devotional Calendar' => 'Bathroom Collection',
    'Red and Black X-Frame Classroom Furniture' => 'School Furniture',
    'Red, Gold, and Blue Basketball Hoop' => 'School Furniture',
    'Rolling wooden cubby cabinet' => 'Hostel Furniture',
    'Round Stool' => 'Industrial Storage',
    'Rows of Burgundy Seats in a Bright Classroom' => 'School Furniture',
    'Silver Key Safe with Open Door' => 'Office Furniture',
    'Simple outdoor wooden utility desk' => 'Industrial Storage',
    'Single Beige Student Desk Chair' => 'School Furniture',
    'Single Wooden School Desk and Bench' => 'School Furniture',
    'Six-Compartment Steel Locker' => 'Industrial Storage',
    'Six-Door Steel Locker Cabinet' => 'Industrial Storage',
    'Sky Blue Apollo Rack Outdoors' => 'Industrial Storage',
    'Softly Lit Cabinet Display Room' => 'Office Furniture',
    'Striped Basketball Hoop Under Blue Skies' => 'School Furniture',
    'Tall 18-Compartment Gray Metal Locker Cabinet' => 'Industrial Storage',
    'Two-Compartment Silver Metal Locker' => 'Hostel Furniture',
    'Wall-Mounted Letter Box' => 'Letter Box',
    'Warm Wooden Storage Bench in Modern Lobby' => 'Office Furniture',
    'Warm Wooden Wardrobes by the Window' => 'Hostel Furniture',
    'White Industrial Chair Against Mint Wall' => 'Industrial Storage',
    'White Lecture Chair with Writing Tablet' => 'Educational Furniture',
    'White Metal Wardrobe with Open Doors' => 'Hostel Furniture',
    'White Rolling Tool Cabinet with Green Shelves' => 'Industrial Storage',
    'Wooden Storage Bed with Open Drawers' => 'Hostel Furniture',
    'Wooden Study Cabinet with Desk' => 'Hostel Furniture',
    'Woodgrain Classroom Desk and Bench Set' => 'School Furniture',
    'Yellow Platform Table Outdoors' => 'School Furniture',
];

$CATEGORY_KEYWORDS = [
    'Office Furniture' => [
        'office desk','executive desk','ceo desk','computer table','office table',
        'manager table','conference table','meeting table','reception table',
        'office chair','executive chair','visitor chair','workstation','cubicle',
        'office sofa','office cabinet','mobile pedestal','mobile filing','pedestal',
        'filing cabinet','storage cabinet','bookshelf','office rack','office cupboard',
        'office locker','office','desk','executive','workstation','cabinet','pedestal',
        'locker','reception','cubicle','drafting table','modular workstation',
        'minimal desk','minimalist desk','minimalist table','minimal table',
        'modern desk','naval blue wooden desk','rolling utility cart','lobby bench',
        'storage bench','corner study desk','study nook','peach study set',
        'pastel study nook','workshop table','workshop stool','waiting bench',
        'cafeteria table','key cabinet','key safe','display cabinet','ivory tusk',
        'lavender pedestal table','wood top workshop table','beige seat tripod',
    ],
    'Educational Furniture' => [
        'library table','library chair','library rack','reading table','reading chair',
        'laboratory table','laboratory stool','lab bench','science table',
        'lecture stand','podium','training desk','training chair','college furniture',
        'lecture chair','writing tablet','tablet arm','tablet-arm','blue pipe fitting',
        'notice board','step bench','blue desk training','training room',
    ],
    'School Furniture' => [
        'student desk','dual desk','single desk','desk bench','student chair',
        'school chair','school bench','teacher table','teacher chair','kids table',
        'kids chair','nursery table','nursery chair','activity table','activity chair',
        'classroom desk','classroom furniture','school stool','kindergarten','nursery',
        'play equipment','playground','slide','swings','basketball','outdoor school',
        'school desk','classroom chair','colorful children','children table',
        'children chair','kids table set','rainbow cubby','playground slide',
        'twin slide','colour-blocked swings','multishelf cabinet against blue',
        'colourful tiered shelf','orange motorcycle graphic chair','striped basketball',
        'red basketball','red gold blue basketball','outdoor student',
        'classroom notice board','student desk chair','blue backdrop school desk',
        'blue corner study desk set','green stool outdoor','woodgrain classroom',
        'minimal desk and bench','single wooden school desk','orange desk and bench',
        'orange yellow outdoor school desk','colourful outdoor school desk set',
        'colourful stools','rows of burgundy seats','x-frame classroom',
        'blue and white two-tier','blue and white worksite benches','floral cups pink',
        'yellow platform table','modern table with colourful chairs','minimal mint corner',
        'multicolour children table set','simple outdoor wooden utility desk',
    ],
    'Hospital Furniture' => [
        'hospital bed','patient bed','icu bed','fowler bed','semi fowler',
        'over bed table','bedside locker','hospital chair','doctor table',
        'doctor chair','medicine cabinet','hospital rack','hospital stool',
        'examination table','crash cart','hospital trolley','hospital','medical',
        'clinic','patient','semi-fowler','overbed',
    ],
    'Hostel Furniture' => [
        'hostel bed','bunk bed','metal bed','steel bed','hostel locker','wardrobe',
        'cupboard','hostel table','study table','hostel chair','hostel rack',
        'hostel cabinet','student wardrobe','hostel','dormitory','metal frame bed',
        'wooden storage bed','wooden study cabinet','warm wooden wardrobes',
        'open wardrobe by the pillar','white metal wardrobe','rolling wooden cubby',
        'mobile beechwood storage cabinet','cream storage cabinet','cream workshop',
        'cream utility hopper','softly lit cabinet display room','open white key cabinet',
        'blue work jacket inside open steel locker',
    ],
    'Industrial Storage' => [
        'industrial rack','warehouse rack','heavy duty rack','steel rack','storage rack',
        'long span rack','slotted angle rack','pallet rack','warehouse shelf',
        'industrial shelf','industrial cabinet','ss wire rack','wire shelving',
        'wire rack','chrome wire','white rolling tool cabinet','industrial storage',
        'sky blue apollo rack','modern white-tier shelving','tiered shelf',
        'patterned tapestry shelf','tall 18-compartment','nine compartment',
        'six door steel locker cabinet','six compartment steel locker',
        'two compartment silver metal locker','minimalist taupe metal locker',
        'metal storage cabinet interior','empty wooden shelf','apollo rack',
        'heavy-duty rack','warehouse','galvanized rack','slotted angle',
    ],
    'Bathroom Collection' => [
        'bathroom cabinet','vanity','mirror cabinet','wash basin cabinet',
        'bathroom shelf','storage shelf','towel rack','bathroom rack',
        'washroom cabinet','bathroom storage','red heart mirror','heart mirror',
        'devotional calendar','decorative tile plumbing fixture','bathroom','mirror',
        'washroom','plumbing','tiled bathroom fixture',
    ],
    'Letter Box' => [
        'letter box','mailbox','mail box','apartment letter box','society letter box',
        'wall mounted letter box','outdoor letter box','wall-mounted letter box',
        'letterbox','mail box','wall letter','mail','letter',
    ],
];

function normalizeForOverride(string $s): string {
    $s = str_replace('_', ' ', $s);
    $s = preg_replace('/\s+/', ' ', $s) ?? $s;
    $s = str_replace(["\u{2018}", "\u{2019}"], "'", $s);
    return trim($s);
}

function lookupStrictOverride(string $rawName, array $overrides): ?string {
    $key = normalizeForOverride($rawName);
    if (isset($overrides[$key])) return $overrides[$key];
    foreach ($overrides as $k => $v) {
        if (normalizeForOverride($k) === $key) return $v;
    }
    return null;
}

$CANONICAL_CATEGORIES = [
    ['id' => '1', 'slug' => 'office-furniture',      'name' => 'Office Furniture'],
    ['id' => '2', 'slug' => 'educational-furniture', 'name' => 'Educational Furniture'],
    ['id' => '3', 'slug' => 'school-furniture',      'name' => 'School Furniture'],
    ['id' => '4', 'slug' => 'hospital-furniture',    'name' => 'Hospital Furniture'],
    ['id' => '5', 'slug' => 'hostel-furniture',      'name' => 'Hostel Furniture'],
    ['id' => '6', 'slug' => 'industrial-storage',    'name' => 'Industrial Storage'],
    ['id' => '7', 'slug' => 'bathroom-collection',   'name' => 'Bathroom Collection'],
    ['id' => '8', 'slug' => 'letter-boxes',          'name' => 'Letter Box'],
];

$categoryByName = [];
$categoryStmt = $pdo->query('SELECT id, name, slug FROM categories ORDER BY id ASC');
while ($row = $categoryStmt->fetch()) {
    $categoryByName[mb_strtolower($row['name'])] = (int)$row['id'];
}
foreach ($CANONICAL_CATEGORIES as $c) {
    $k = mb_strtolower($c['name']);
    if (!isset($categoryByName[$k])) {
        $ins = $pdo->prepare('INSERT INTO categories (name, slug, sort_order, is_featured, status, created_at, updated_at) VALUES (?, ?, ?, 1, "active", NOW(), NOW())');
        $ins->execute([$c['name'], $c['slug'], (int)$c['id']]);
        $categoryByName[$k] = (int)$pdo->lastInsertId();
    }
}

$defaultSellerUserEmail = 'seller@opcieas.com';
$defaultSellerUserName = 'OPCIEAS Default Seller';
$defaultSellerCompanyName = 'OPCIEAS Default Seller';

$seedUserStmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$seedUserStmt->execute([$defaultSellerUserEmail]);
$userId = (int)$seedUserStmt->fetchColumn();
if (!$userId) {
    $createUser = $pdo->prepare('INSERT INTO users (name, email, company, country, password_hash, role, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
    $createUser->execute([
        $defaultSellerUserName,
        $defaultSellerUserEmail,
        $defaultSellerCompanyName,
        'India',
        password_hash('password123', PASSWORD_BCRYPT),
        'seller',
        'Approved',
    ]);
    $userId = (int)$pdo->lastInsertId();
}

$seedSellerStmt = $pdo->prepare('SELECT id FROM seller_profiles WHERE user_id = ? LIMIT 1');
$seedSellerStmt->execute([$userId]);
$sellerId = (int)$seedSellerStmt->fetchColumn();
if (!$sellerId) {
    $createSeller = $pdo->prepare('INSERT INTO seller_profiles (user_id, company_name, country, state, city, pincode, phone, status, verification_status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
    $createSeller->execute([
        $userId,
        $defaultSellerCompanyName,
        'India',
        'Delhi',
        'New Delhi',
        '110001',
        '9999999999',
        'Approved',
        'Approved',
    ]);
    $sellerId = (int)$pdo->lastInsertId();
}

function cleanProductName(string $raw): string {
    $name = str_replace('_', ' ', $raw);
    $noiseWords = ['final', 'copy', 'duplicate', 'new', 'edited'];
    $prev = '';
    do {
        $prev = $name;
        $name = preg_replace('/(\s*\(\d+\)\s*|_\d+|-\d+|\s*(?:' . implode('|', $noiseWords) . ')\s*)+$/i', '', $name) ?? $name;
    } while ($name !== $prev);
    $name = preg_replace('/\s+/', ' ', $name) ?? $name;
    $name = trim($name);
    return preg_replace_callback('/\b\w/u', static function ($m) {
        return mb_strtoupper($m[0]);
    }, $name) ?? $name;
}

function toKebab(string $str): string {
    $str = mb_strtolower($str);
    $str = preg_replace('/[^a-z0-9]+/u', '-', $str) ?? $str;
    return trim($str, '-');
}

$folderNameToCategory = [
    'office' => 'Office Furniture',
    'office furniture' => 'Office Furniture',
    'educational' => 'Educational Furniture',
    'educational furniture' => 'Educational Furniture',
    'school' => 'School Furniture',
    'school furniture' => 'School Furniture',
    'hospital' => 'Hospital Furniture',
    'hospital furniture' => 'Hospital Furniture',
    'hostel' => 'Hostel Furniture',
    'hostel furniture' => 'Hostel Furniture',
    'industrial' => 'Industrial Storage',
    'industrial storage' => 'Industrial Storage',
    'warehouse' => 'Industrial Storage',
    'bathroom' => 'Bathroom Collection',
    'bathroom collection' => 'Bathroom Collection',
    'washroom' => 'Bathroom Collection',
    'letter box' => 'Letter Box',
    'letter boxes' => 'Letter Box',
    'mailbox' => 'Letter Box',
    'mail box' => 'Letter Box',
];

function detectCategoryByName(string $rawName, array $CANONICAL_CATEGORIES, array $CATEGORY_KEYWORDS): string {
    $lowered = str_replace('_', ' ', mb_strtolower($rawName));
    $scored = [];
    foreach ($CANONICAL_CATEGORIES as $c) {
        $keywords = $CATEGORY_KEYWORDS[$c['name']] ?? [];
        $score = 0;
        foreach ($keywords as $kw) {
            $k = mb_strtolower($kw);
            if (str_contains($lowered, $k)) {
                $score += 3 + max(0, count(explode(' ', $k)) - 1);
            }
        }
        if (str_contains($lowered, mb_strtolower($c['name']))) $score += 8;
        $scored[] = ['name' => $c['name'], 'score' => $score];
    }
    usort($scored, static fn($a, $b) => $b['score'] - $a['score']);
    $best = $scored[0] ?? null;
    return ($best && $best['score'] > 0) ? $best['name'] : 'Office Furniture';
}

function resolveCategoryByPath(string $path, string $fallback, array $CANONICAL_CATEGORIES): string {
    $lower = mb_strtolower($path);
    foreach ($CANONICAL_CATEGORIES as $c) {
        $pieces = [$c['name'], $c['slug'], str_replace('-', ' ', $c['slug'])];
        foreach ($pieces as $p) {
            if (str_contains($lower, mb_strtolower($p))) return $c['name'];
        }
    }
    $segments = explode('/', $lower);
    $folder = $segments[count($segments) - 2] ?? '';
    $folderCat = detectCategoryByName($folder, $CANONICAL_CATEGORIES, $GLOBALS['CATEGORY_KEYWORDS']);
    if ($folderCat === 'Office Furniture') return $fallback;
    return $folderCat;
}

function detectCategory(string $path, string $rawName, array $overrides, array $folderMap, array $CANONICAL_CATEGORIES, array $CATEGORY_KEYWORDS): string {
    $strict = lookupStrictOverride($rawName, $overrides);
    if ($strict !== null) return $strict;
    $lower = str_replace('\\', '/', mb_strtolower($path));
    $segments = explode('/', $lower);
    $productFolderIndex = array_search('product', $segments, true);
    if ($productFolderIndex !== false && $productFolderIndex < count($segments) - 2) {
        $subFolder = $segments[$productFolderIndex + 1];
        if (isset($folderMap[$subFolder])) return $folderMap[$subFolder];
    }
    $directFolder = $segments[count($segments) - 2] ?? '';
    if (isset($folderMap[$directFolder])) return $folderMap[$directFolder];
    $nameCat = detectCategoryByName($rawName, $CANONICAL_CATEGORIES, $CATEGORY_KEYWORDS);
    if ($nameCat !== 'Office Furniture') return $nameCat;
    return resolveCategoryByPath($path, $nameCat, $CANONICAL_CATEGORIES);
}

function hashCode(string $s): int {
    $h = 0;
    $len = strlen($s);
    for ($i = 0; $i < $len; $i++) {
        $h = (int)((31 * $h + ord($s[$i])) | 0);
    }
    return abs($h);
}

function pickByHash(array $arr, string $seed, int $idx = 0) {
    return $arr[(hashCode($seed . '||' . (string)$idx)) % max(1, count($arr))];
}

$PRICE_BRACKETS = [
    'Office Furniture'      => [12000, 120000],
    'Educational Furniture' => [5000, 55000],
    'School Furniture'      => [1500, 40000],
    'Hospital Furniture'    => [15000, 110000],
    'Hostel Furniture'      => [8000, 45000],
    'Industrial Storage'    => [7000, 80000],
    'Bathroom Collection'   => [5000, 35000],
    'Letter Box'            => [1500, 55000],
];

$DEFAULT_MATERIALS = [
    'Office Furniture'      => ['Engineered Wood + Powder-Coated Steel', 'Laminate / Veneer Top', 'Mild Steel Frame'],
    'Educational Furniture' => ['Mild Steel + Engineered Wood', 'Powder-Coated Frame', 'Anti-Scratch Laminate'],
    'School Furniture'      => ['Mild Steel + HDPE / Plastic', 'Powder-Coated Tubular Frame', 'Anti-Scratch Laminated Top'],
    'Hospital Furniture'    => ['CR / MS Steel Frame', 'Epoxy / Powder-Coated Finish', 'Foam + Leatherette Upholstery'],
    'Hostel Furniture'      => ['Mild Steel / Steel', 'Powder-Coated Finish', 'Engineered Wood Work Surface'],
    'Industrial Storage'    => ['Mild Steel', 'Powder-Coated / Galvanized', 'Heavy-Duty Uprights & Beams'],
    'Bathroom Collection'   => ['Stainless Steel 304 / PVC / Marine Ply', 'Waterproof Finish', 'Rust-Resistant Hardware'],
    'Letter Box'            => ['Stainless Steel 304 / Mild Steel / ABS Plastic / Wood', 'Powder-Coated or Polished', 'Lockable Mechanism'],
];

$DEFAULT_FINISHES = [
    'Office Furniture'      => ['Laminate', 'High Gloss', 'Veneer', 'Powder Coated'],
    'Educational Furniture' => ['Powder Coated', 'Laminate', 'Matte'],
    'School Furniture'      => ['Powder Coated', 'Laminate', 'UV Protected'],
    'Hospital Furniture'    => ['Epoxy Coated', 'Powder Coated', 'Easy-Clean Surface'],
    'Hostel Furniture'      => ['Powder Coated', 'Laminate', 'Matte'],
    'Industrial Storage'    => ['Powder Coated', 'Galvanized', 'Chrome Plated'],
    'Bathroom Collection'   => ['Mirror Polish', 'Brushed', 'Waterproof Laminate', 'Chrome'],
    'Letter Box'            => ['Powder Coated', 'Mirror Polish', 'Natural Wood Polish', 'Matte'],
];

$DEFAULT_COLORS = [
    'Office Furniture'      => ['Walnut Brown', 'Maple Cream', 'White', 'Charcoal Grey', 'Black', 'Navy Blue'],
    'Educational Furniture' => ['Maple', 'Grey', 'White', 'Blue', 'Beige'],
    'School Furniture'      => ['Sky Blue', 'Red', 'Green', 'Yellow', 'Orange', 'Pink', 'White', 'Woodgrain'],
    'Hospital Furniture'    => ['White', 'Sky Blue', 'Beige', 'Medical Grey', 'Sage Green'],
    'Hostel Furniture'      => ['Charcoal Grey', 'Black', 'Royal Blue', 'Ivory', 'Woodgrain'],
    'Industrial Storage'    => ['Silver Grey', 'Blue', 'Orange', 'Galvanized Silver', 'Ral 7035'],
    'Bathroom Collection'   => ['Chrome Silver', 'Brushed Gold', 'White', 'Matte Black', 'Woodgrain'],
    'Letter Box'            => ['Stainless Steel', 'Black', 'Brown Wood', 'White', 'Gold', 'Grey'],
];

$DEFAULT_SIZE_RANGES = [
    'Office Furniture'      => ['Standard', 'Compact', 'Executive (1.8m+)', 'Boardroom (3.0m+)'],
    'Educational Furniture' => ['2-Seater', '4-Seater', '6-Seater', 'Modular'],
    'School Furniture'      => ['Nursery / Kids', 'Primary', 'Secondary', 'Adult / Teacher'],
    'Hospital Furniture'    => ['Single', 'Bariatric', 'Paediatric', 'Standard Clinical'],
    'Hostel Furniture'      => ['Single Cot', 'Bunk Bed', '3-Tier', 'With Storage'],
    'Industrial Storage'    => ['8 Shelves / Level', '12 Shelves / Level', '16 Shelves / Level', '20+ Shelves / Level', 'Custom'],
    'Bathroom Collection'   => ['600mm', '800mm', '1000mm', '1200mm', 'Custom'],
    'Letter Box'            => ['Single Unit', '8 Flats', '16 Flats', '24 Flats', 'Custom Cluster'],
];

$FEATURE_POOL = [
    'Office Furniture' => [
        'Ergonomic Design', 'Cable Management', 'Lockable Drawers', 'Modular Construction',
        'Scratch-Resistant Laminate', 'Powder-Coated Frame', 'Ample Storage', 'Stain-Resistant Surface',
        'Tender-Ready Specs', 'Export-Grade Finish', 'Custom Sizes Available', 'Bulk Manufacturing Capacity',
    ],
    'Educational Furniture' => [
        'Heavy-Duty Frame', 'Ergonomic Sizing', 'Scratch-Resistant Top', 'Stackable / Compact Storage',
        'Powder-Coated Finish', 'Age-Appropriate Design', 'Easy To Clean', 'Long-Lasting Welds',
        'Bulk Supply Ready', 'Tender Compliant', 'Anti-Skid Feet', 'Classroom Tested',
    ],
    'School Furniture' => [
        'Safe Rounded Edges', 'Ergonomic', 'Scratch-Resistant', 'Heavy-Duty Tubular Frame',
        'Powder-Coated', 'UV-Stabilized Plastic Components', 'Anti-Skid Feet', 'Weather-Resistant (Outdoor Models)',
        'Age-Appropriate Height', 'Bulk Supply', 'Tender Ready', 'Low Maintenance',
    ],
    'Hospital Furniture' => [
        'Hygienic, Easy-Clean Surfaces', 'Medical-Grade Powder Coat', 'Adjustable Height / Backrest',
        'Side Rails (Models Applicable)', 'Lockable Castors', 'Corrosion-Resistant Frame',
        'Clinical-Validated Design', 'Standardized For Tenders', 'Custom Configurations', 'Bulk Supply Ready',
    ],
    'Hostel Furniture' => [
        'Strong Welded Steel Frame', 'Powder-Coated Corrosion Resistance', 'Safety Rails (Bunk Models)',
        'Integrated Ladder', 'Ample Under-Bed Storage', 'Lockable Compartments',
        'Dormitory-Ready', 'Bulk Order Friendly', 'Easy Assembly Kit', 'Long Lifespan',
    ],
    'Industrial Storage' => [
        'High Load Capacity', 'Boltless / Bolted Assembly', 'Adjustable Shelf Levels',
        'Powder-Coated or Galvanized Finish', 'Rust Resistant', 'Modular Expansion',
        'Forklift-Compatible (Racks)', 'Export Packaging', 'Custom Dimensions', 'Warehouse Tested',
    ],
    'Bathroom Collection' => [
        'Waterproof / Humidity-Resistant', 'Rust-Resistant Hardware', 'Premium Finish (Chrome / SS)',
        'Easy Wall Mounting', 'Ample Storage', 'Stain-Resistant Surface',
        'Commercial-Grade Build', 'Hospitality & Office Ready', 'Mirror / Shelf Options', 'Low Maintenance',
    ],
    'Letter Box' => [
        'Secure Locking Mechanism', 'Weatherproof Construction', 'Anti-Corrosion Finish',
        'Easy Installation (Wall / Floor)', 'Apartment Cluster Configurations', 'Multiple Flats Modules',
        'Newspaper Holder (Optional)', 'Keyed Lock', 'Mail Theft Protection', 'Custom Branding',
    ],
];

function generateSpecs(string $name, string $cat, array $DEFAULT_MATERIALS, array $DEFAULT_FINISHES, array $DEFAULT_COLORS, array $DEFAULT_SIZE_RANGES): array {
    $materials = $DEFAULT_MATERIALS[$cat] ?? $DEFAULT_MATERIALS['Office Furniture'];
    $finishes = $DEFAULT_FINISHES[$cat] ?? $DEFAULT_FINISHES['Office Furniture'];
    $colors = $DEFAULT_COLORS[$cat] ?? $DEFAULT_COLORS['Office Furniture'];
    $sizes = $DEFAULT_SIZE_RANGES[$cat] ?? $DEFAULT_SIZE_RANGES['Office Furniture'];
    $dimsByCat = [
        'Office Furniture'      => 'As per selected model / Custom',
        'Educational Furniture' => 'Standard institutional sizes / Custom',
        'School Furniture'      => 'Student ergonomic sizing / Age-specific',
        'Hospital Furniture'    => 'Clinical standard sizes / Custom',
        'Hostel Furniture'      => 'Standard cot / bunk dimensions',
        'Industrial Storage'    => 'Load capacity 200–2000 kg / level, custom height & width',
        'Bathroom Collection'   => 'Wall-mount / countertop as per model',
        'Letter Box'            => 'As per flat-count module / Custom cluster',
    ];
    return [
        'Material'       => pickByHash($materials, $name, 1) . ' | ' . pickByHash($materials, $name, 2),
        'Finish'         => pickByHash($finishes, $name, 3),
        'Color Options'  => pickByHash($colors, $name, 4) . ', ' . pickByHash($colors, $name, 5) . ', ' . pickByHash($colors, $name, 6),
        'Size Options'   => pickByHash($sizes, $name, 7) . ' | ' . pickByHash($sizes, $name, 8),
        'Dimensions'     => $dimsByCat[$cat] ?? 'Custom',
        'Warranty'       => '1 Year Manufacturer Warranty (Terms Apply)',
        'Compliance'     => 'ISO 9001:2015 | NSIC | MSME | Export Ready',
    ];
}

function generateFeatures(string $name, string $cat, array $FEATURE_POOL): array {
    $pool = $FEATURE_POOL[$cat] ?? $FEATURE_POOL['Office Furniture'];
    $seed = hashCode($name . '|' . $cat);
    $features = [];
    $cursor = 0;
    $count = 4 + ($seed % 3);
    while (count($features) < $count && $cursor < count($pool) * 2) {
        $item = $pool[(hashCode($name . '|f|' . $cursor)) % max(1, count($pool))];
        if (!in_array($item, $features, true)) $features[] = $item;
        $cursor++;
    }
    return $features;
}

function generateShortDesc(string $cleanName, string $cat): string {
    $prefix = [
        'Office Furniture'      => 'Premium commercial-grade',
        'Educational Furniture' => 'Institutional-quality',
        'School Furniture'      => 'Heavy-duty, student-ready',
        'Hospital Furniture'    => 'Hygiene-friendly clinical',
        'Hostel Furniture'      => 'Durable dormitory',
        'Industrial Storage'    => 'Heavy-duty industrial',
        'Bathroom Collection'   => 'Waterproof premium',
        'Letter Box'            => 'Secure, weatherproof',
    ];
    $p = $prefix[$cat] ?? 'Premium';
    return "{$p} " . mb_strtolower($cleanName) . " designed for " . mb_strtolower($cat) . " use with durable finish and export-ready quality.";
}

function generateLongDesc(string $cleanName, string $cat): string {
    return "{$cleanName} from OPCIEAS is engineered for demanding " . mb_strtolower($cat) . " environments. Manufactured with premium raw materials, strict quality control and export-grade finishing, this product combines durable construction with ergonomic design and low long-term maintenance. Suitable for government tender supply, bulk institutional orders, commercial establishments, hospitality, education campuses, industrial facilities and export markets. Every unit is backed by standardized dimensions, quality assurance documentation and customization flexibility for finishes, colors and sizes to meet project-specific requirements. Contact our team for RFQ, bulk pricing, installation support and tender-ready technical specifications.";
}

function slugifyUnique(string $text, array &$slugCounts): string {
    $slug = toKebab($text);
    if (!$slug) $slug = 'product-' . time();
    if (isset($slugCounts[$slug])) {
        $slugCounts[$slug]++;
        return "{$slug}-{$slugCounts[$slug]}";
    }
    $slugCounts[$slug] = 1;
    return $slug;
}

$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($assetRoot));
$candidates = [];
foreach ($it as $fileInfo) {
    $filename = $fileInfo->getFilename();
    if ($filename === '.' || $filename === '..' || !$fileInfo->isFile()) continue;
    $path = $fileInfo->getPathname();
    $ext = strtolower($fileInfo->getExtension());
    if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'avif'], true)) continue;
    $rawName = $fileInfo->getBasename('.' . $ext);
    if (!$rawName) continue;
    $cleanName = cleanProductName($rawName);
    $category = detectCategory($path, $rawName, $STRICT_RAW_NAME_OVERRIDE, $folderNameToCategory, $CANONICAL_CATEGORIES, $CATEGORY_KEYWORDS);
    $candidates[] = ['path' => $path, 'rawName' => $rawName, 'cleanName' => $cleanName, 'category' => $category];
}

function extractVariantScore(string $rawName): int {
    if (preg_match('/\((\d+)\)\s*$/', $rawName, $m)) return (int)$m[1] + 1;
    return 0;
}

$groupMap = [];
foreach ($candidates as $c) {
    $groupKey = $c['category'] . '__' . toKebab($c['cleanName']);
    if (!isset($groupMap[$groupKey])) {
        $groupMap[$groupKey] = [
            'key' => $groupKey,
            'cleanName' => $c['cleanName'],
            'category' => $c['category'],
            'variants' => [],
        ];
    }
    $found = false;
    foreach ($groupMap[$groupKey]['variants'] as $v) {
        if ($v['path'] === $c['path']) { $found = true; break; }
    }
    if (!$found) {
        $groupMap[$groupKey]['variants'][] = [
            'path' => $c['path'],
            'rawName' => $c['rawName'],
            'score' => extractVariantScore($c['rawName']),
        ];
    }
}

foreach ($groupMap as &$g) {
    usort($g['variants'], static fn($a, $b) => $a['score'] - $b['score']);
    $galleryPaths = array_map(static fn($v) => $v['path'], $g['variants']);
    $nonVariant = null;
    foreach ($g['variants'] as $v) {
        if ($v['score'] === 0) { $nonVariant = $v['path']; break; }
    }
    $primaryPath = $nonVariant ?: $galleryPaths[0];
    if (!in_array($primaryPath, $galleryPaths, true)) array_unshift($galleryPaths, $primaryPath);
    $g['primary'] = $primaryPath;
    $g['gallery'] = $galleryPaths;
}
unset($g);

$PRODUCT_IMAGE_GROUPS = array_values($groupMap);
usort($PRODUCT_IMAGE_GROUPS, static fn($a, $b) => strcmp($a['cleanName'], $b['cleanName']));

$cleanNameCounts = [];
$slugCounts = [];
$processed = 0;
$migrated = 0;
$imagesCopied = 0;

foreach ($PRODUCT_IMAGE_GROUPS as $idx => $g) {
    $cat = $g['category'];
    $categoryKey = mb_strtolower($cat);
    $categoryId = $categoryByName[$categoryKey] ?? null;
    if (!$categoryId) continue;
    $displayName = $g['cleanName'];
    $nameKey = mb_strtolower($displayName);
    $cleanNameCounts[$nameKey] = ($cleanNameCounts[$nameKey] ?? 0) + 1;
    $countForName = $cleanNameCounts[$nameKey];
    $finalName = $countForName > 1 ? "{$displayName} (Variant {$countForName})" : $displayName;
    $slug = slugifyUnique($finalName, $slugCounts);
    $priceSeed = hashCode($g['cleanName'] . '|' . $g['key']);
    $featuredSeed = $priceSeed % 7;
    $featured = $featuredSeed === 0 ? 1 : 0;
    [$lo, $hi] = $PRICE_BRACKETS[$cat] ?? [5000, 50000];
    $mid = (int)(($lo + $hi) / 2);
    $p1 = $lo + ($priceSeed % max(1, (int)(($mid - $lo) / 500))) * 500;
    $p2 = $mid + (($priceSeed >> 3) % max(1, (int)(($hi - $mid) / 1000))) * 1000;
    $a = min($p1, $p2);
    $b = max($p1, $p2);
    $price = max($a, $lo);
    $discountPrice = max(1000, (int)round($price * 0.9));
    $stockQuantity = 25 + ($priceSeed % 95);
    $shortDesc = generateShortDesc($g['cleanName'], $cat);
    $description = generateLongDesc($g['cleanName'], $cat);
    $specs = generateSpecs($g['cleanName'], $cat, $DEFAULT_MATERIALS, $DEFAULT_FINISHES, $DEFAULT_COLORS, $DEFAULT_SIZE_RANGES);
    $features = generateFeatures($g['cleanName'], $cat, $FEATURE_POOL);
    $sku = 'OPC-' . strtoupper(substr(md5($slug), 0, 8));
    $metaTitle = "{$finalName} - OPCIEAS";
    $metaDescription = $shortDesc;

    $insertSql = 'INSERT INTO products (
        seller_id, category_id, name, slug, sku, short_description, description,
        features, specifications, price, discount_price, stock_quantity,
        is_featured, status, meta_title, meta_description, created_at, updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ON DUPLICATE KEY UPDATE
        seller_id = VALUES(seller_id),
        category_id = VALUES(category_id),
        name = VALUES(name),
        sku = VALUES(sku),
        short_description = VALUES(short_description),
        description = VALUES(description),
        features = VALUES(features),
        specifications = VALUES(specifications),
        price = VALUES(price),
        discount_price = VALUES(discount_price),
        stock_quantity = VALUES(stock_quantity),
        is_featured = VALUES(is_featured),
        status = VALUES(status),
        meta_title = VALUES(meta_title),
        meta_description = VALUES(meta_description),
        updated_at = NOW()';

    try {
        $pdo->prepare($insertSql)->execute([
            $sellerId,
            $categoryId,
            $finalName,
            $slug,
            $sku,
            $shortDesc,
            $description,
            json_encode($features, JSON_UNESCAPED_UNICODE),
            json_encode($specs, JSON_UNESCAPED_UNICODE),
            $price,
            $discountPrice,
            $stockQuantity,
            $featured,
            'Published',
            $metaTitle,
            $metaDescription,
        ]);
    } catch (Throwable $e) {
        fwrite(STDERR, "Product upsert failed ({$finalName}): " . $e->getMessage() . "\n");
        continue;
    }

    $productIdStmt = $pdo->prepare('SELECT id FROM products WHERE slug = ? LIMIT 1');
    $productIdStmt->execute([$slug]);
    $productId = (int)$productIdStmt->fetchColumn();
    if (!$productId) continue;

    $pdo->prepare('DELETE FROM product_images WHERE product_id = ?')->execute([$productId]);
    $pdo->prepare('DELETE FROM product_specs WHERE product_id = ?')->execute([$productId]);

    $uploadedRelPaths = [];
    $prodSubDir = date('Y') . '/' . date('m');
    $targetDir = $uploadDir . DIRECTORY_SEPARATOR . $prodSubDir;
    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0755, true);
    }

    foreach ($g['gallery'] as $imgIdx => $srcPath) {
        if (!file_exists($srcPath)) continue;
        $ext = strtolower(pathinfo($srcPath, PATHINFO_EXTENSION));
        if ($ext === 'jpg') $ext = 'jpeg';
        $safeBase = preg_replace('/[^a-z0-9]+/u', '_', toKebab($g['cleanName'])) ?? 'product';
        $safeName = time() . '_' . $safeBase . '_' . substr(bin2hex(random_bytes(4)), 0, 6) . '.' . $ext;
        $destPath = $targetDir . DIRECTORY_SEPARATOR . $safeName;
        $copied = false;
        if (@copy($srcPath, $destPath)) {
            @chmod($destPath, 0644);
            $copied = true;
            $imagesCopied++;
        } else {
            $destPath = $srcPath;
        }
        $relUrl = $uploadUrlPrefix . $prodSubDir . '/' . $safeName;
        $relPath = $copied ? 'uploads/products/' . $prodSubDir . '/' . $safeName : $srcPath;
        $uploadedRelPaths[] = ['url' => $relUrl, 'path' => $relPath];
    }

    foreach ($uploadedRelPaths as $imgIdx => $info) {
        $isPrimary = $imgIdx === 0 ? 1 : 0;
        $imgStmt = $pdo->prepare('INSERT INTO product_images (product_id, image_path, image_url, alt_text, sort_order, is_primary, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $imgStmt->execute([
            $productId,
            $info['path'],
            $info['url'],
            $finalName,
            $imgIdx,
            $isPrimary,
        ]);
    }

    foreach ($specs as $specKey => $specValue) {
        $specStmt = $pdo->prepare('INSERT INTO product_specs (product_id, spec_key, spec_value, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())');
        $specStmt->execute([$productId, $specKey, $specValue]);
    }

    $migrated++;
    $processed++;
    if ($migrated % 10 === 0) fwrite(STDOUT, "  ... migrated {$migrated} products so far\n");
}

$dbCountStmt = $pdo->query('SELECT COUNT(*) FROM products WHERE status = "Published"');
$dbCount = (int)$dbCountStmt->fetchColumn();
$totalDbCountStmt = $pdo->query('SELECT COUNT(*) FROM products');
$totalDbCount = (int)$totalDbCountStmt->fetchColumn();

fwrite(STDOUT, "\n");
fwrite(STDOUT, "============================================================\n");
fwrite(STDOUT, " PRODUCT MIGRATION REPORT\n");
fwrite(STDOUT, "============================================================\n");
fwrite(STDOUT, sprintf("  Discovered product groups (frontend logic): %d\n", count($PRODUCT_IMAGE_GROUPS)));
fwrite(STDOUT, sprintf("  Products migrated in DB:                   %d\n", $migrated));
fwrite(STDOUT, sprintf("  Published products in DB:                  %d\n", $dbCount));
fwrite(STDOUT, sprintf("  Total products in DB (any status):         %d\n", $totalDbCount));
fwrite(STDOUT, sprintf("  Images copied to uploads/products:         %d\n", $imagesCopied));
fwrite(STDOUT, "============================================================\n");
