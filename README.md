# WooCommerce SKU images
- Contributors: lluisma
- Tags: woocommerce, images, SKU, admin
- Requires PHP: 5.3
- WC Tested up to: 5.2
- License: GPLv2 or later
- License URI: http://www.gnu.org/licenses/gpl-2.0.html

Manage images for WooCommerce products: massive upload, attachment and delete.

Just upload your images via FTP to the preload folder (defined on [settings](#Settings) page) and start to manage your product images.

## Description ##

*WooCommerce SKU Images* performs a simple way to attach images to WooCommerce products (as thumbnail and gallery images).

The only **requisite** is to set the image names according the pattern `[skuValue]_[index].jpg`.
Example: for a product with 1234 as SKU value, we must name its images as following: 
* ``1234_1.jpg``
* ``1234_2.jpg``
* ``1234_3.jpg``
* etc..

The plugin admits some other formats that will be normalized on the publishing process:
* Blank spaces: ``1234 1.jpg`` >> ``1234_1.jpg`` 
* Dashes: ``1234-1.jpg`` >> ``1234_1.jpg`` 
* Parenthesis: ``1234(1).jpg`` >> ``1234_1.jpg`` 

### Pending uploads ###

The list shows all the existing images in the preload folder and checks if each image name is SKU_index formatted.

You can only attach those images with a correct SKU could. The selected ones will be renamed with the normalized name (removing blank spaces, dashes, parenthesis, etc...), resized and attached to the correspondent SKU product as thubmnail and/or gallery images. 

Theses images can be **added** tot  the existing gallery product images (even the first one as thumbnail if it does not exist) or you can **replace** the existing ones, which will be removed from the correspondent upload folder.

Of course, you also can remove the images that you don't want to be attached.


### Unnattached SKU images ###
Sometimes you may find images with SKU_index formated name on ``wp-contents/uploads`` folders that hasn't been attached to correspondent SKU product (or have been deattached from).

Just select them and will be setted as thubmnail and/or gallery images, added to the existing ones or just replaced them (which will be removed).

As previous case, you can also can remove the images that you don't want to be attached.

### Free SKU attachments ###
There may be attachments with SKU_index formated title but not linked to correspondent SKU product. You can assign these attachments to its product (just adding them to existing images or replacing - and removing - them) or remove those that you want.

## Installation ##

1. Upload the plugin files to the `/wp-content/plugins/woocommerce-sku-images` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Install and activate WooCommerce if you haven't already done so

### Settings ###
* **Preload Folder**: Path to the directory where you upload your images via FTP
* **Items per page**: Number of rows showed on images lists (default: 20)
* **Optimized image size**: For large-sized images you can set this value (in pixels) to resize them and make them lighter.

## Source code ##
The source code is freely available in [GitHub](https://github.com/Lluisma/woocommerce-sku-images)

## Changelog ##
### 1.0.0 ###
* Beta release

