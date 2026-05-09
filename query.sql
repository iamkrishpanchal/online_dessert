
SELECT 'Products Table Columns:' AS info;
SHOW COLUMNS FROM tbl_product;

SELECT '---' AS separator;
SELECT 'Total Products:' AS info;
SELECT COUNT(*) as total_products FROM tbl_product;

SELECT '---' AS separator;
SELECT 'First 5 Products:' AS info;
SELECT * FROM tbl_product LIMIT 5;

SELECT '---' AS separator;
SELECT 'Categories:' AS info;
SELECT * FROM tbl_categories;

SELECT '---' AS separator;
SELECT 'Products by Category:' AS info;
SELECT catId as category_id, COUNT(*) as product_count FROM tbl_product GROUP BY catId;
