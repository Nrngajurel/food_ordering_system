<?php

namespace App\Services;

use App\Models\Food;
use Phpml\Clustering\KMeans;
use Phpml\Classification\KNearestNeighbors;
use App\Models\Order;
use App\Models\Product;

class ProductRecombinationService
{
    /**
     * Recombines products for a specific user based on order history using KMeans clustering.
     *
     * @param int $userId
     * @return array
     */
    public function recombineProductsKMeans(int $userId): array
    {
        // Step 1: Extract data from the orders table
        $orders = Order::join('food_order', 'order.id', '=', 'food_order.order_id')
            ->select('order.user_id', 'food_order.food_id', 'food_order.quantity')
            ->get()
            ->toArray();

        // Step 2: Prepare the samples and labels for KMeans
        $samples = [];
        $labels = [];

        foreach ($orders as $order) {
            $samples[] = [$order['food_id'], $order['quantity']];
            $labels[] = $order['user_id'];
        }

        // Step 3: Apply KMeans clustering (group similar users)
        $kmeans = new KMeans(3); // Define the number of clusters (adjust based on your data)
        $clusters = $kmeans->cluster($samples);

        // Step 4: Recombine products for the target user (userId)
        $recombinedProductIds = [];

        foreach ($clusters as $cluster) {
            foreach ($cluster as $order) {
                if ($labels[array_search($order, $samples)] == $userId) {
                    // Get products from similar users
                    foreach ($cluster as $otherOrder) {
                        if ($otherOrder != $order) {
                            $recombinedProductIds[] = $otherOrder[0]; // Recombine food_id
                        }
                    }
                }
            }
        }

        // Step 5: Load actual product details
        return $this->loadProductDetails($recombinedProductIds);
    }

    /**
     * Recombines products for a specific user using K-Nearest Neighbors (KNN).
     *
     * @param int $userId
     * @return array
     */
    public function recombineProductsKNN(int $userId): array
    {
        // Step 1: Extract data from the orders table
        $orders = Order::join('food_order', 'order.id', '=', 'food_order.order_id')
            ->select('order.user_id', 'food_order.food_id', 'food_order.quantity')
            ->get()
            ->toArray();

        // Step 2: Prepare the samples and labels for KNN
        $samples = [];
        $labels = [];

        foreach ($orders as $order) {
            $samples[] = [$order['food_id'], $order['quantity']];
            $labels[] = $order['user_id'];
        }

        // Step 3: Train the KNN classifier
        $knn = new KNearestNeighbors();
        $knn->train($samples, $labels);

        // Step 4: Find similar users/products for userId using KNN
        $recombinedProductIds = [];

        foreach ($orders as $order) {
            if ($order['user_id'] == $userId) {
                // Predict the closest user
                $predictedUserId = $knn->predict([$order['food_id'], $order['quantity']]);

                // Fetch products ordered by the predicted similar user
                foreach ($orders as $similarOrder) {
                    if ($similarOrder['user_id'] == $predictedUserId && $similarOrder['food_id'] != $order['food_id']) {
                        $recombinedProductIds[] = $similarOrder['food_id']; // Recombine food_id
                    }
                }
            }
        }

        // Step 5: Load actual product details
        return $this->loadProductDetails($recombinedProductIds);
    }

    /**
     * Load product details by product IDs.
     *
     * @param array $productIds
     * @return array
     */
    private function loadProductDetails(array $productIds): array
    {
        // Fetch product details from the database based on product IDs
        $products = Food::whereIn('id', $productIds)->take(4)->get();

        return $products->toArray(); // Return the product details as an array
    }
}
