USE online_dessert;

CREATE TABLE IF NOT EXISTS tbl_product (
  product_id int NOT NULL AUTO_INCREMENT,
  pname varchar(255) NOT NULL,
  price decimal(10,2) NOT NULL,
  catId int NOT NULL,
  productImg varchar(255) DEFAULT NULL,
  PRIMARY KEY (product_id),
  KEY catId (catId),
  CONSTRAINT tbl_product_ibfk_1 FOREIGN KEY (catId) REFERENCES tbl_categories (categories_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
