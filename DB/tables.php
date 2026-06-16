<?php
$conn = new mysqli("localhost","root", "password", "Agri_Project");
// Checking connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "✅ Connected successfully to MySQL server<br>";

// SQL to create tables
//1.Users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    full_name VARCHAR(150) NOT NULL,
    phone_number VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    role ENUM('farmer','buyer','transporter','admin') NOT NULL,
    password_hash  VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS subscriptions (
id               CHAR(36)      PRIMARY KEY DEFAULT (UUID()),
  farmer_id        CHAR(36)      NOT NULL,
  plan             ENUM('monthly','annual') NOT NULL,
  status           ENUM('active','expired','cancelled') NOT NULL DEFAULT 'active',
  starts_at        DATETIME      NOT NULL,
  expires_at       DATETIME      NOT NULL,
  mpesa_reference  VARCHAR(50)   NOT NULL UNIQUE,
  amount_paid      DECIMAL(10,2) NOT NULL,
  created_at       DATETIME      DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
CREATE INDEX idx_subscriptions_farmer ON subscriptions(farmer_id);
CREATE INDEX idx_subscriptions_expires ON subscriptions(expires_at);

CREATE TABLE listings (
  id                    CHAR(36)      PRIMARY KEY DEFAULT (UUID()),
  farmer_id             CHAR(36)      NOT NULL,
  crop_type             VARCHAR(100)  NOT NULL,
  quantity              DECIMAL(10,2) NOT NULL,
  unit                  ENUM('bags','tons','kg','crates','litres') NOT NULL,
  price_per_unit        DECIMAL(10,2) NOT NULL,
  general_region        VARCHAR(100)  NOT NULL,
  farm_address          TEXT          NOT NULL,
  farm_latitude         DECIMAL(10,8),
  farm_longitude        DECIMAL(11,8),
  harvest_readiness_date DATE         NOT NULL,
  quality_notes         TEXT,
  status                ENUM('available','booked','sold','expired') NOT NULL DEFAULT 'available',
  created_at            DATETIME      DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
CREATE INDEX idx_listings_status ON listings(status);
CREATE INDEX idx_listings_crop ON listings(crop_type);
CREATE INDEX idx_listings_region ON listings(general_region);

CREATE TABLE listing_photos (
  id          CHAR(36)     PRIMARY KEY DEFAULT (UUID()),
  listing_id  CHAR(36)     NOT NULL,
  photo_url   VARCHAR(500) NOT NULL,
  uploaded_at DATETIME     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX idx_photos_listing ON listing_photos(listing_id);


CREATE TABLE orders (
  id                     CHAR(36)      PRIMARY KEY DEFAULT (UUID()),
  listing_id             CHAR(36)      NOT NULL,
  buyer_id               CHAR(36)      NOT NULL,
  transporter_id         CHAR(36),
  status                 ENUM(
                           'booked',
                           'farmer_confirmed',
                           'ready_for_pickup',
                           'in_transit',
                           'delivered',
                           'completed'
                         ) NOT NULL DEFAULT 'booked',
  transport_fee          DECIMAL(10,2) NOT NULL,
  produce_amount         DECIMAL(10,2) NOT NULL,
  buyer_delivery_address TEXT          NOT NULL,
  buyer_latitude         DECIMAL(10,8),
  buyer_longitude        DECIMAL(11,8),
  farmer_confirmed_at    DATETIME,
  picked_up_at           DATETIME,
  delivered_at           DATETIME,
  created_at             DATETIME      DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (listing_id)     REFERENCES listings(id)  ON DELETE RESTRICT,
  FOREIGN KEY (buyer_id)       REFERENCES users(id)     ON DELETE RESTRICT,
  FOREIGN KEY (transporter_id) REFERENCES users(id)     ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_buyer ON orders(buyer_id);
CREATE INDEX idx_orders_transporter ON orders(transporter_id);

CREATE TABLE payments (
  id               CHAR(36)      PRIMARY KEY DEFAULT (UUID()),
  order_id         CHAR(36),
  subscription_id  CHAR(36),
  payer_id         CHAR(36)      NOT NULL,
  recipient_id     CHAR(36),
  payment_type     ENUM(
                     'subscription',
                     'transport_fee',
                     'produce_balance',
                     'payout_farmer',
                     'payout_transporter',
                     'platform_markup'
                   ) NOT NULL,
  amount           DECIMAL(10,2) NOT NULL,
  mpesa_reference  VARCHAR(50)   NOT NULL UNIQUE,
  status           ENUM('pending','confirmed','failed') NOT NULL DEFAULT 'pending',
  paid_at          DATETIME      DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id)        REFERENCES orders(id)        ON DELETE RESTRICT,
  FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE RESTRICT,
  FOREIGN KEY (payer_id)        REFERENCES users(id)         ON DELETE RESTRICT,
  FOREIGN KEY (recipient_id)    REFERENCES users(id)         ON DELETE RESTRICT
) ENGINE=InnoDB;
CREATE INDEX idx_payments_order ON payments(order_id);
CREATE INDEX idx_payments_mpesa ON payments(mpesa_reference);
CREATE INDEX idx_payments_status ON payments(status);

CREATE TABLE disputes (
  id          CHAR(36)  PRIMARY KEY DEFAULT (UUID()),
  order_id    CHAR(36)  NOT NULL,
  raised_by   CHAR(36)  NOT NULL,
  description TEXT      NOT NULL,
  status      ENUM('open','resolved','dismissed') NOT NULL DEFAULT 'open',
  resolved_by CHAR(36),
  created_at  DATETIME  DEFAULT CURRENT_TIMESTAMP,
  resolved_at DATETIME,
  FOREIGN KEY (order_id)    REFERENCES orders(id) ON DELETE RESTRICT,
  FOREIGN KEY (raised_by)   REFERENCES users(id)  ON DELETE RESTRICT,
  FOREIGN KEY (resolved_by) REFERENCES users(id)  ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE INDEX idx_disputes_order ON disputes(order_id);
CREATE INDEX idx_disputes_status ON disputes(status); ";
//Queries//

if ($conn->multi_query($sql) === TRUE) {
    echo "✅ All tables created successfully!<br>";
} else {
    echo "❌ Error creating tables: " . $conn->error . "<br>";
}

// Show all tables created
$result = $conn->query("SHOW TABLES");
echo "<br>📋 Tables in database:<br>";
while ($row = $result->fetch_row()) {
    echo "- " . $row[0] . "<br>";
}

$conn->close();
?>

