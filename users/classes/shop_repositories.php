<?php
/**
 * Thin repository layer to organize data access behind clear classes.
 * These repositories delegate to existing methods on your `database` class
 * when available, falling back to safe defaults. This keeps index.php clean
 * while avoiding risky schema duplication.
 */

// Note: No namespaces used to match the existing codebase style.

class ProductRepository {
    private $db;

    public function __construct($db) { $this->db = $db; }

    /**
     * Get all products.
     * @return array
     */
    public function all(): array {
        if (method_exists($this->db, 'fetchAllProducts')) {
            return (array) $this->db->fetchAllProducts();
        }
        return [];
    }

    /**
     * Get recommended products for a customer.
     * @param int|string|null $customerId
     * @return array
     */
    public function recommendedForCustomer($customerId): array {
        if (method_exists($this->db, 'getRecommendedProducts')) {
            return (array) $this->db->getRecommendedProducts($customerId);
        }
        return [];
    }

    /**
     * Get bestseller products limited by $limit.
     * @param int $limit
     * @return array
     */
    public function bestsellers(int $limit = 4): array {
        if (method_exists($this->db, 'getBestsellerProducts')) {
            return (array) $this->db->getBestsellerProducts($limit);
        }
        return [];
    }
}

class CategoryRepository {
    private $db;
    public function __construct($db) { $this->db = $db; }

    /**
     * Get all categories.
     * @return array
     */
    public function all(): array {
        if (method_exists($this->db, 'fetchAllCategories')) {
            return (array) $this->db->fetchAllCategories();
        }
        return [];
    }
}

class OrderRepository {
    private $db;
    public function __construct($db) { $this->db = $db; }

    /**
     * Get a customer's orders grouped by status.
     * @param int $customerId
     * @return array
     */
    public function getOrdersByStatus(int $customerId): array {
        if (method_exists($this->db, 'getOrdersByStatus')) {
            return (array) $this->db->getOrdersByStatus($customerId);
        }
        // Default grouped structure if unavailable
        return [
            'To Ship' => [],
            'To Receive' => [],
            'Delivered' => []
        ];
    }

    /**
     * Optionally expose order items via Order class if needed later.
     * Keeping placeholder for future expansion.
     */
}

class AddressRepository {
    private $db;
    public function __construct($db) { $this->db = $db; }

    /**
     * Get saved delivery address for a customer.
     * Delegates to database->getSavedDeliveryAddress if present.
     * @param int $customerId
     * @return array
     */
    public function getSavedDeliveryAddress(int $customerId): array {
        if (method_exists($this->db, 'getSavedDeliveryAddress')) {
            $addr = $this->db->getSavedDeliveryAddress($customerId);
            return is_array($addr) ? $addr : [];
        }
        return [];
    }
}

class ReviewRepository {
    private $db;
    public function __construct($db) { $this->db = $db; }

    /**
     * Get average ratings per product.
     * @return array
     */
    public function getAverageRatings(): array {
        if (method_exists($this->db, 'getAverageRatings')) {
            return (array) $this->db->getAverageRatings();
        }
        return [];
    }
}

?>
