-- คลังใหญ่เวชภัณฑ์มิใช่ยา
CREATE TABLE non_drug_warehouse (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255),
    unit VARCHAR(50),
    quantity INT,
    price DECIMAL(10,2),
    received_date DATE,
    expiry_date DATE,
    remaining INT,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- รายการใช้เวชภัณฑ์มิใช่ยา
CREATE TABLE non_drug_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    non_drug_id INT,
    used_qty INT,
    used_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (non_drug_id) REFERENCES non_drug_warehouse(id) ON DELETE CASCADE
);
