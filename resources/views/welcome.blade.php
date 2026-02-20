<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic Cart Pricing Demo</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
            color: #333;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        h1, h2, h3 {
            color: #0056b3;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-top: 20px;
        }
        pre {
            background-color: #e9e9e9;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        select, input[type="text"] {
            width: calc(100% - 22px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 5px;
            transition: background-color 0.2s ease;
        }
        button:hover {
            background-color: #0056b3;
        }
        .action-button {
            padding: 12px 20px;
            font-size: 16px;
            margin-top: 15px;
        }
        .error {
            color: red;
            font-weight: bold;
            margin-top: 10px;
        }
        .cart-item-display {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid #eee;
            padding: 10px;
            margin-bottom: 8px;
            background-color: #fafafa;
            border-radius: 4px;
        }
        .cart-item-info {
            flex-grow: 1;
        }
        .cart-item-controls {
            display: flex;
            align-items: center;
        }
        .qty-input {
            width: 40px;
            text-align: center;
            margin: 0 5px;
            -moz-appearance: textfield; 
        }
        .qty-input::-webkit-outer-spin-button,
        .qty-input::-webkit-inner-spin-button {
            -webkit-appearance: none; 
            margin: 0;
        }
        .qty-button {
            padding: 5px 10px;
            font-size: 16px;
        }
        #currentCartList {
            min-height: 100px;
            border: 1px dashed #ccc;
            padding: 10px;
            margin-bottom: 20px;
            background-color: #fcfcfc;
            border-radius: 4px;
        }
        .product-select-option {
            font-weight: bold;
        }
        .product-select-option span {
            float: right;
            font-weight: normal;
            color: #555;
        }
        #productSelect option {
            padding: 8px;
        }
        #productSelect option:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Dynamic Cart Pricing Demo</h1>
        <p>Select products to add to your cart, adjust quantities, and apply coupons.</p>

        <h2>Add Products to Cart</h2>
        <label for="productSelect">Select a Product:</label>
        <select id="productSelect">
            <option value="">-- Select a product to add --</option>
            <!-- Products will be loaded here by JavaScript -->
        </select>

        <h3>Your Current Cart</h3>
        <div id="currentCartList">
            <p>Your cart is empty. Add some products!</p>
        </div>

        <h2>Apply Coupon</h2>
        <label for="couponCodeInput">Coupon Code:</label>
        <input type="text" id="couponCodeInput" placeholder="e.g., NEWUSER, FESTIVE">
        <button class="action-button" onclick="calculateCart()">Calculate Final Price</button>

        <h2>Calculation Result</h2>
        <div id="resultDisplay">
            <p>Build your cart and click "Calculate Final Price" to see the output.</p>
        </div>
    </div>

    <script>
        // --- Configuration ---
        const API_BASE_URL = 'http://localhost:8000/api'; 

        // --- Global State ---
        let availableProducts = []; 
        let currentCart = {};     

        async function fetchJson(url, options = {}) {
            try {
                const response = await fetch(url, options);
                const data = await response.json().catch(() => ({ message: 'Could not parse JSON response.' }));

                if (!response.ok) {
                    let errorMessage = `API Error: ${response.status} ${response.statusText}`;
                    if (data && data.message) {
                        errorMessage += `<br>Message: ${data.message}`;
                    }
                    if (data && data.errors) {
                        errorMessage += '<br>Errors:<ul>';
                        for (const key in data.errors) {
                            errorMessage += `<li>${key}: ${data.errors[key].join(', ')}</li>`;
                        }
                        errorMessage += '</ul>';
                    }
                    document.getElementById('resultDisplay').innerHTML = `<p class="error">${errorMessage}</p>`;
                    throw new Error(errorMessage);k
                }
                return data;
            } catch (error) {
                console.error("Network or Fetch error:", error);
                document.getElementById('resultDisplay').innerHTML = `<p class="error">
                    Failed to connect to the API or unknown error occurred.<br>
                    Ensure your Laravel server is running and accessible at <code>${API_BASE_URL}</code>.<br>
                    Error: ${error.message}
                </p>`;
                throw error; 
            }
        }

        // --- Product Management ---
        async function loadProductsIntoDropdown() {
            try {
                const data = await fetchJson(`${API_BASE_URL}/products`);
                
                availableProducts = data; 

                const productSelect = document.getElementById('productSelect');
                productSelect.innerHTML = '<option value="">-- Select a product to add --</option>'; 

                availableProducts.forEach(product => {
                    const option = document.createElement('option');
                    option.value = product.product_id;
                    option.textContent = `${product.name} | ₹${product.price.toFixed(2)}`;
                    productSelect.appendChild(option);
                });
            } catch (error) {
                console.error("Failed to load products (caught by loadProductsIntoDropdown):", error);
                if (document.getElementById('productSelect').innerHTML === '<option value="">-- Select a product to add --</option>') {
                   document.getElementById('productSelect').innerHTML = '<option value="">-- Error loading products --</option>';
                }
            }
        }

        function addProductToCart(productId) {
            const product = availableProducts.find(p => p.product_id === parseInt(productId));
            if (!product) return;

            if (currentCart[productId]) {
                // If product already in cart, increment quantity
                if (currentCart[productId].qty < product.stock_quantity) {
                    currentCart[productId].qty++;
                } else {
                    alert(`Cannot add more "${product.name}". Max stock (${product.stock_quantity}) reached!`);
                }
            } else {
                // Add new product to cart
                currentCart[productId] = { ...product, qty: 1 };
            }
            renderCart();
        }

        function updateCartItemQuantity(productId, change) {
            if (!currentCart[productId]) return;

            const product = availableProducts.find(p => p.product_id === parseInt(productId));
            let newQty = currentCart[productId].qty + change;

            if (newQty < 1) {
                delete currentCart[productId];
            } else if (newQty > product.stock_quantity) {
                 alert(`Cannot add more "${product.name}". Max stock (${product.stock_quantity}) reached!`);
            } else {
                currentCart[productId].qty = newQty;
            }
            renderCart();
        }

        function removeCartItem(productId) {
            if (confirm("Are you sure you want to remove this item?")) {
                delete currentCart[productId];
                renderCart();
            }
        }

        function renderCart() {
            const currentCartList = document.getElementById('currentCartList');
            currentCartList.innerHTML = ''; 

            const cartItemsArray = Object.values(currentCart);

            if (cartItemsArray.length === 0) {
                currentCartList.innerHTML = '<p>Your cart is empty. Add some products!</p>';
                return;
            }

            cartItemsArray.forEach(item => {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'cart-item-display';
                itemDiv.innerHTML = `
                    <div class="cart-item-info">
                        <strong>${item.name}</strong><br>
                        Category: ${item.category}<br>
                        Price: ₹${item.price.toFixed(2)}
                    </div>
                    <div class="cart-item-controls">
                        <button class="qty-button" onclick="updateCartItemQuantity(${item.product_id}, -1)">-</button>
                        <input type="number" class="qty-input" value="${item.qty}" min="1" readonly>
                        <button class="qty-button" onclick="updateCartItemQuantity(${item.product_id}, 1)">+</button>
                        <button style="background-color: #dc3545;" onclick="removeCartItem(${item.product_id})">Remove</button>
                    </div>
                `;
                currentCartList.appendChild(itemDiv);
            });
        }


        async function calculateCart() {
            const couponCode = document.getElementById('couponCodeInput').value.trim();
            const resultDisplay = document.getElementById('resultDisplay');
            resultDisplay.innerHTML = '<p>Calculating...</p>'; // Show loading message

            const cartItemsForAPI = Object.values(currentCart).map(item => ({
                product_id: item.product_id,
                name: item.name,
                price: item.price,
                category: item.category,
                qty: item.qty
            }));

            const payload = {
                cart: cartItemsForAPI,
                coupon_code: couponCode === '' ? null : couponCode
            };

            if (cartItemsForAPI.length === 0) {
                resultDisplay.innerHTML = '<p class="error">Your cart is empty. Please add items before calculating.</p>';
                return;
            }


            try {
                const data = await fetchJson(`${API_BASE_URL}/cart/calculate`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                resultDisplay.innerHTML = `
                    <h2>Final Pricing Details</h2>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                `;

            } catch (error) {
                console.error("Cart calculation failed (caught by calculateCart):", error);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadProductsIntoDropdown();
            renderCart(); 

            document.getElementById('productSelect').addEventListener('change', (event) => {
                const selectedProductId = event.target.value;
                if (selectedProductId) {
                    addProductToCart(selectedProductId);
                    event.target.value = "";
                }
            });
        });
    </script>
</body>
</html>