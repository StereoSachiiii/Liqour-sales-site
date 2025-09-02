<?php
include('sql-config.php');

// Function to get new arrivals (latest 4 products)
function getNewArrivals($conn) {
    $sql = "SELECT l.liqour_id, l.name, l.description, l.price, l.image_url, c.name as category_name 
            FROM liqours l 
            JOIN liqour_categories c ON l.category_id = c.liqour_category_id 
            ORDER BY l.created_at DESC 
            LIMIT 4";
    
    $result = $conn->query($sql);
    $products = [];
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    return $products;
}

// Function to get products by category
function getProductsByCategory($conn, $category_name, $limit = 4) {
    $sql = "SELECT l.liqour_id, l.name, l.description, l.price, l.image_url, c.name as category_name 
            FROM liqours l 
            JOIN liqour_categories c ON l.category_id = c.liqour_category_id 
            WHERE c.name = ? 
            ORDER BY l.created_at DESC 
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $category_name, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    return $products;
}

// Function to get featured products (highest rated or marked as featured)
function getFeaturedProducts($conn, $limit = 4) {
    $sql = "SELECT l.liqour_id, l.name, l.description, l.price, l.image_url, c.name as category_name,
            AVG(r.rating) as avg_rating, COUNT(r.rating) as review_count
            FROM liqours l 
            JOIN liqour_categories c ON l.category_id = c.liqour_category_id 
            LEFT JOIN reviews r ON l.liqour_id = r.liqour_id
            GROUP BY l.liqour_id, l.name, l.description, l.price, l.image_url, c.name
            ORDER BY avg_rating DESC, review_count DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    return $products;
}

// Function to get all categories
function getCategories($conn) {
    $sql = "SELECT liqour_category_id, name FROM liqour_categories ORDER BY name";
    $result = $conn->query($sql);
    
    $categories = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    return $categories;
}

// Function to search products
function searchProducts($conn, $search_term) {
    $search_term = '%' . $search_term . '%';
    $sql = "SELECT l.liqour_id, l.name, l.description, l.price, l.image_url, c.name as category_name 
            FROM liqours l 
            JOIN liqour_categories c ON l.category_id = c.liqour_category_id 
            WHERE l.name LIKE ? OR l.description LIKE ? OR c.name LIKE ?
            ORDER BY l.name";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $search_term, $search_term, $search_term);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    return $products;
}

// Get data for the homepage
$newArrivals = getNewArrivals($conn);
$liquorProducts = getProductsByCategory($conn, 'Liquor', 4);
$wineProducts = getProductsByCategory($conn, 'Wine', 4);
$featuredProducts = getFeaturedProducts($conn, 4);
$categories = getCategories($conn);

// Handle AJAX search requests
if (isset($_GET['action']) && $_GET['action'] === 'search' && isset($_GET['q'])) {
    header('Content-Type: application/json');
    $searchResults = searchProducts($conn, $_GET['q']);
    echo json_encode($searchResults);
    exit;
}
?>