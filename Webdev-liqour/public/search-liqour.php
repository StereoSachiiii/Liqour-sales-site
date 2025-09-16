<?php
session_start();
include('../Backend/sql-config.php');

header('Content-Type: application/json');

// Input parameters
$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all'; // 'all', 'name', 'category'
$minPrice = isset($_GET['minPrice']) && is_numeric($_GET['minPrice']) ? floatval($_GET['minPrice']) : null;
$maxPrice = isset($_GET['maxPrice']) && is_numeric($_GET['maxPrice']) ? floatval($_GET['maxPrice']) : null;
$sort = isset($_GET['sort']) ? $_GET['sort'] : ''; // 'price_asc' | 'price_desc'

$results = [];

// Helper function for price conditions
function addPriceConditions(&$sql, &$types, &$params, $minPrice, $maxPrice) {
    if($minPrice !== null){
        $sql .= " AND l.price >= ?";
        $types .= "d";
        $params[] = $minPrice;
    }
    if($maxPrice !== null){
        $sql .= " AND l.price <= ?";
        $types .= "d";
        $params[] = $maxPrice;
    }
}

// No query & no filter → return all active liquors
if($query === '' && $minPrice === null && $maxPrice === null){
    $sqlAll = "SELECT l.liqour_id, l.name, l.price, l.image_url, c.name AS category_name
               FROM liqours l
               JOIN liqour_categories c ON l.category_id = c.liqour_category_id
               WHERE l.is_active = 1 AND c.is_active = 1
               ORDER BY l.liqour_id DESC
               LIMIT 50";
    $resAll = $conn->query($sqlAll);
    if($resAll && $resAll->num_rows > 0){
        while($row = $resAll->fetch_assoc()){
            $results[] = $row;
        }
    }
} else {
    $likeQuery = "%$query%";

    // Function to execute search
    function executeSearch($conn, $sql, $types, $params){
        $out = [];
        $stmt = $conn->prepare($sql);
        if(!empty($types)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        while($row = $res->fetch_assoc()) $out[] = $row;
        $stmt->close();
        return $out;
    }

    $combinedResults = [];

    // Search by name
    if($filter === 'all' || $filter === 'name'){
        $sqlName = "SELECT l.liqour_id, l.name, l.price, l.image_url, c.name AS category_name
                    FROM liqours l
                    JOIN liqour_categories c ON l.category_id = c.liqour_category_id
                    WHERE l.is_active = 1 AND c.is_active = 1";

        $types = '';
        $params = [];
        if($query !== ''){
            $sqlName .= " AND l.name LIKE ?";
            $types .= "s";
            $params[] = $likeQuery;
        }

        addPriceConditions($sqlName, $types, $params, $minPrice, $maxPrice);

        // Sorting
        if($sort === 'price_asc') $sqlName .= " ORDER BY l.price ASC";
        elseif($sort === 'price_desc') $sqlName .= " ORDER BY l.price DESC";
        else $sqlName .= " ORDER BY l.liqour_id DESC";

        $sqlName .= " LIMIT 50";

        $combinedResults = array_merge($combinedResults, executeSearch($conn, $sqlName, $types, $params));
    }

    // Search by category
    if($filter === 'all' || $filter === 'category'){
        $sqlCat = "SELECT l.liqour_id, l.name, l.price, l.image_url, c.name AS category_name
                   FROM liqours l
                   JOIN liqour_categories c ON l.category_id = c.liqour_category_id
                   WHERE l.is_active = 1 AND c.is_active = 1";

        $types = '';
        $params = [];
        if($query !== ''){
            $sqlCat .= " AND c.name LIKE ?";
            $types .= "s";
            $params[] = $likeQuery;
        }

        addPriceConditions($sqlCat, $types, $params, $minPrice, $maxPrice);

        if($sort === 'price_asc') $sqlCat .= " ORDER BY l.price ASC";
        elseif($sort === 'price_desc') $sqlCat .= " ORDER BY l.price DESC";
        else $sqlCat .= " ORDER BY l.liqour_id DESC";

        $sqlCat .= " LIMIT 50";

        $catResults = executeSearch($conn, $sqlCat, $types, $params);

        // Avoid duplicates
        $existingIds = array_column($combinedResults, 'liqour_id');
        foreach($catResults as $r){
            if(!in_array($r['liqour_id'], $existingIds)) $combinedResults[] = $r;
        }
    }

    $results = $combinedResults;
}

// Return JSON
echo json_encode($results);
