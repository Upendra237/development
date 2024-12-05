<?php require_once 'includes/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Local Business Map</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row h-100">
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="filters-container">
                    <div class="category-header">
                        <h5 class="mb-0">Filter</h5>
                        <div class="category-buttons">
                            <button id="selectAll" class="btn btn-sm btn-outline-primary">Select All</button>
                            <button id="clearAll" class="btn btn-sm btn-outline-secondary">Clear All</button>
                        </div>
                    </div>
                    <div id="categoryList"></div>
                    <div class="search-container">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="searchInput" placeholder="Search businesses...">
                        </div>
                    </div>
                </div>

                <div class="business-list p-3">
                    <h4>Businesses</h4>
                    <div id="businessList"></div>
                </div>
            </div>

            <div class="col-md-9 col-lg-10 map-container">
                <div id="map"></div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        const DEFAULT_LAT = 27.7172;
        const DEFAULT_LNG = 85.3240;
        const DEFAULT_ZOOM = 13;
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="assets/js/business-service.js"></script>
    <script src="assets/js/map-controller.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>