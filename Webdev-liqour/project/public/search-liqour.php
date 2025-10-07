<?php
session_start();
include('../Backend/sql-config.php');

header('Content-Type: application/json');

try {
    // Enhanced input parameters
    $query = trim($_GET['query'] ?? '');
    $category = (int)($_GET['category'] ?? 0);
    $minPrice = isset($_GET['minPrice']) && is_numeric($_GET['minPrice']) ? floatval($_GET['minPrice']) : null;
    $maxPrice = isset($_GET['maxPrice']) && is_numeric($_GET['maxPrice']) ? floatval($_GET['maxPrice']) : null;
    $sort = $_GET['sort'] ?? '';
    $section = $_GET['section'] ?? 'liquor';
    $stockFilter = $_GET['stockFilter'] ?? ''; // 'in-stock', 'low-stock', 'out-of-stock'
    $limit = min((int)($_GET['limit'] ?? 12), 50); // Max 50 items
    $offset = (int)($_GET['offset'] ?? 0);

    // Build base query with proper stock calculation
    $baseQuery = "SELECT l.liqour_id, l.name, l.price, l.image_url, l.description,
                         c.name AS category_name, c.liqour_category_id,
                         COALESCE(SUM(s.quantity), 0) as total_stock
                  FROM liqours l
                  JOIN liqour_categories c ON l.category_id = c.liqour_category_id
                  LEFT JOIN stock s ON l.liqour_id = s.liqour_id AND s.is_active = 1
                  WHERE l.is_active = 1 AND c.is_active = 1";

    // Build conditions and parameters
    $conditions = [];
    $params = [];
    $types = "";

    // Search by name or description
    if (!empty($query)) {
        $conditions[] = "(l.name LIKE ? OR l.description LIKE ? OR c.name LIKE ?)";
        $searchTerm = "%$query%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        $types .= "sss";
    }

    // Filter by category
    if ($category > 0) {
        $conditions[] = "l.category_id = ?";
        $params[] = $category;
        $types .= "i";
    }

    // Price range filters
    if ($minPrice !== null && $minPrice > 0) {
        $conditions[] = "l.price >= ?";
        $params[] = $minPrice;
        $types .= "d";
    }

    if ($maxPrice !== null && $maxPrice > 0) {
        $conditions[] = "l.price <= ?";
        $params[] = $maxPrice;
        $types .= "d";
    }

    // Add conditions to query
    if (!empty($conditions)) {
        $baseQuery .= " AND " . implode(" AND ", $conditions);
    }

    // Store the base query for count query (before GROUP BY is added)
    $baseQueryForCount = $baseQuery;

    // Group by for proper stock calculation
    $baseQuery .= " GROUP BY l.liqour_id, l.name, l.price, l.image_url, l.description, c.name, c.liqour_category_id";

    // Apply stock filtering after grouping (using HAVING)
    $havingConditions = [];
    switch ($stockFilter) {
        case 'in-stock':
            $havingConditions[] = "total_stock > 5";
            break;
        case 'low-stock':
            $havingConditions[] = "total_stock > 0 AND total_stock <= 5";
            break;
        case 'out-of-stock':
            $havingConditions[] = "total_stock = 0";
            break;
    }

    if (!empty($havingConditions)) {
        $baseQuery .= " HAVING " . implode(" AND ", $havingConditions);
    }

    // Add sorting based on section and sort parameter
    $orderClause = "";
    switch ($section) {
        case 'new-arrivals':
            $orderClause = " ORDER BY l.liqour_id DESC";
            break;
        case 'featured':
            $orderClause = " ORDER BY total_stock DESC, l.price DESC";
            break;
        default:
            // Handle various sort options
            switch ($sort) {
                case 'price_asc':
                    $orderClause = " ORDER BY l.price ASC";
                    break;
                case 'price_desc':
                    $orderClause = " ORDER BY l.price DESC";
                    break;
                case 'name_asc':
                    $orderClause = " ORDER BY l.name ASC";
                    break;
                case 'name_desc':
                    $orderClause = " ORDER BY l.name DESC";
                    break;
                case 'stock_desc':
                    $orderClause = " ORDER BY total_stock DESC";
                    break;
                case 'newest':
                    $orderClause = " ORDER BY l.liqour_id DESC";
                    break;
                default:
                    $orderClause = " ORDER BY l.liqour_id DESC";
            }
    }

    $baseQuery .= $orderClause;

    // Add pagination
    $baseQuery .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    // Execute main query
    $stmt = $conn->prepare($baseQuery);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = [
            'liqour_id' => (int)$row['liqour_id'],
            'name' => htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'),
            'price' => (float)$row['price'],
            'image_url' => htmlspecialchars($row['image_url'] ?? '', ENT_QUOTES, 'UTF-8'),
            'description' => htmlspecialchars($row['description'] ?? '', ENT_QUOTES, 'UTF-8'),
            'category_name' => htmlspecialchars($row['category_name'], ENT_QUOTES, 'UTF-8'),
            'category_id' => (int)$row['liqour_category_id'],
            'total_stock' => (int)$row['total_stock']
        ];
    }

    // FIXED: Get total count for pagination using a separate, cleaner approach
    $countQuery = "SELECT COUNT(DISTINCT l.liqour_id) as total 
                   FROM liqours l
                   JOIN liqour_categories c ON l.category_id = c.liqour_category_id
                   LEFT JOIN stock s ON l.liqour_id = s.liqour_id AND s.is_active = 1
                   WHERE l.is_active = 1 AND c.is_active = 1";

    // Add the same conditions as the main query
    if (!empty($conditions)) {
        $countQuery .= " AND " . implode(" AND ", $conditions);
    }

    // For stock filtering, we need to use a subquery approach for the count
    if (!empty($havingConditions)) {
        $countQuery = "SELECT COUNT(*) as total FROM (
                          SELECT l.liqour_id, COALESCE(SUM(s.quantity), 0) as total_stock
                          FROM liqours l
                          JOIN liqour_categories c ON l.category_id = c.liqour_category_id
                          LEFT JOIN stock s ON l.liqour_id = s.liqour_id AND s.is_active = 1
                          WHERE l.is_active = 1 AND c.is_active = 1";
        
        if (!empty($conditions)) {
            $countQuery .= " AND " . implode(" AND ", $conditions);
        }
        
        $countQuery .= " GROUP BY l.liqour_id
                         HAVING " . implode(" AND ", $havingConditions) . "
                       ) as filtered_products";
    }

    // Prepare count query parameters (exclude limit and offset)
    $countParams = array_slice($params, 0, -2);
    $countTypes = substr($types, 0, -2);

    $countStmt = $conn->prepare($countQuery);
    if (!empty($countParams)) {
        $countStmt->bind_param($countTypes, ...$countParams);
    }
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalCount = $countResult->fetch_assoc()['total'] ?? 0;

    // Success response
    echo json_encode([
        'success' => true,
        'products' => $products,
        'total' => (int)$totalCount,
        'limit' => $limit,
        'offset' => $offset,
        'has_more' => ($offset + $limit) < $totalCount,
        'filters_applied' => [
            'query' => $query,
            'category' => $category,
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
            'sort' => $sort,
            'stockFilter' => $stockFilter,
            'section' => $section
        ]
    ]);

} catch (Exception $e) {
    // Error response
    error_log("Search error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Search failed. Please try again.',
        'debug' => $e->getMessage() // Remove in production
    ]);
}

$conn->close();
?>