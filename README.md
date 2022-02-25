# WooCommerce Mix and Match - Categories

### What's This?

Experimental mini-extension for [WooCommerce Mix and Match](https://woocommerce.com/products/woocommerce-mix-and-match-products//) that adds products as mix and match options by product category.

### Important

1. This is provided as is and does not receive priority support.
2. Will not be performant on categories with large numbers of products.
3. Please test thoroughly before using in production.

### Usage

In the "Mix and Match" tab of the Product Data metabox you will now see a "Use Product Category" input with 2 options.

1. "Use default" - meaning use the normal Mix and Match method of searching and selecting individual products by their product title.
2. "Use product categories for contents" - search for and select product categories. Mix and Match will then display all products in those categories as options for the Mix and M atch container product.

![screenshot of product data meta box](https://user-images.githubusercontent.com/507025/79798240-eeaba680-8315-11ea-95b3-07991394a52e.png)

### Known issues

RC4 fixes an issue with some front-end calls to admin-ajax.php remove the products from the cart. But the issue could still happen if the user is an admin and can switch to the dashboard then return to the cart. The core Mix and Match plugin needs some refactoring to avoid this entirely.

### Automatic plugin updates

Plugin updates can be enabled by installing the [Git Updater](https://git-updater.com/) plugin.