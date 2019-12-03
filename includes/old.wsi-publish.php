<?php

	$dirPRO = ABSPATH . get_option( 'wsi_field_dirpro' );

	$urlPRO = get_site_url() . '/' . get_option( 'wsi_field_dirpro' );


	// Captura dades POST per gestionar les accions a realitzar (si existeixen) -------------------

	$action = (isset($_POST['wsi-action'])) ? $_POST['wsi-action'] : '';
	$arrSel = (isset($_POST['wsi-selection'])) ? explode(',', $_POST['wsi-selection']) : null;


	if ($arrSel) {

		if ($action=='delete') {

			$arrDel   = [];
			$arrNoDel = [];

			foreach ($arrSel as $name) {

				if (unlink( $dirPRO . '/' . $name )) {
			
					$arrDel[] = $name;

				} else {

					$arrNoDel[] = $name;

				}

			}

			if ( count($arrDel)>0 ) {
				add_settings_error( 'wsi_messages', 'wsi_message', __( 'Deleted images: ', 'woocommerce-sku-images' )  . implode(', ', $arrDel), 'updated' );
			}

			if ( count($arrNoDel)>0 ) {
				add_settings_error( 'wsi_messages', 'wsi_message', __( 'Can\'t delete this images: ', 'woocommerce-sku-images' )  . implode(', ', $arrNoDel), 'error' );
			}

		} elseif ($action=='publish') {

			$arrPub   = [];
			$arrNoPub = [];

			// Esborra les imatges existents
/*
		foreach ($arrSel as $id) {

			if (wp_delete_attachment( $id, false )) {

				$arrDel[] = $id;

			} else {

				$arrNoDel[] = $id;

			}

		}
*/

$product_id = 57102;		// Parent Post



$wp_upload_dir = wp_upload_dir();


// * Passem les imatges de l'arxiu temporal al directori d'uploads de Wordpress

$origen = $dirPRO . "/1070_1.jpg";

// Path to a file in the upload directory.
$desti  = $wp_upload_dir['path']. "/1070_1.jpg";

//echo basename( $desti );


rename($origen, $desti);




// Check the type of file. We'll use this as the 'post_mime_type'.
$filetype = wp_check_filetype( basename( $desti ), null );

// Prepare an array of post data for the attachment.
$attachment = array(
    'guid'           => $wp_upload_dir['url'] . '/' . basename( $desti ), 
    'post_mime_type' => $filetype['type'],
    'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $desti ) ),
    'post_content'   => '',
    'post_status'    => 'inherit'
);

// Insert the attachment.
$attach_id = wp_insert_attachment( $attachment, $desti, $product_id );

// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
require_once( ABSPATH . 'wp-admin/includes/image.php' );

// Generate the metadata for the attachment, and update the database record.
$attach_data = wp_generate_attachment_metadata( $attach_id, $desti );

//var_dump($attach_data);
//echo "<br>" . preg_replace( '/\.[^.]+$/', '', basename( $desti ) );

wp_update_attachment_metadata( $attach_id, $attach_data );


set_post_thumbnail( $product_id, $attach_id );



/*
resta

$img_id_array[] = attachment_url_to_postid( $url2 );
$img_id_array[] = attachment_url_to_postid( $url3 );

$img_id_str = implode(',',$img_id_array);


//print_r($arrSel);


update_post_meta($product_id, '_product_image_gallery', $img_id_str);


*/





 /*






//$filename = "https://boixet.cat/arxiu/botiga/1070_1.jpg";

//$url = $dirPRO . '/1070_1.jpg';

$url = 'https://boixet.cat/wp-content/uploads/2019/03/1070-1.jpg';


$img_id = attachment_url_to_postid( $url );

echo $img_id;

set_post_thumbnail($product_id, $img_id);




$img_id_array = [];

$url2 = 'https://boixet.cat/wp-content/uploads/2019/03/1070-2.jpg';
$url3 = 'https://boixet.cat/wp-content/uploads/2019/03/1070-3.jpg';

$img_id_array[] = attachment_url_to_postid( $url2 );
$img_id_array[] = attachment_url_to_postid( $url3 );

$img_id_str = implode(',',$img_id_array);


//print_r($arrSel);


update_post_meta($product_id, '_product_image_gallery', $img_id_str);

//die();

/*
$image_id_array = [];


    //take the first image in the array and set that as the featured image
    set_post_thumbnail($product_id, $image_id_array[0]);

    //if there is more than 1 image - add the rest to product gallery
    if(sizeof($image_id_array) > 1) { 
        array_shift($image_id_array); //removes first item of the array (because it's been set as the featured image already)
        update_post_meta($product_id, '_product_image_gallery', implode(',',$image_id_array)); //set the images id's left over after the array shift as the gallery images
    }
*/



/*
$wp_upload_dir = wp_upload_dir();


// $filename should be the path to a file in the upload directory.
//$filename = '/path/to/uploads/2013/03/filename.jpg';


// The ID of the post this attachment is for.


// Check the type of file. We'll use this as the 'post_mime_type'.
$filetype = wp_check_filetype( basename( $filename ), null );


// Get the path to the upload directory.
$wp_upload_dir = wp_upload_dir();

 


// Prepare an array of post data for the attachment.
$attachment = array(
	'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ), 
	'post_mime_type' => $filetype['type'],
	'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
	'post_content'   => '',
	'post_status'    => 'inherit'
);



// Insert the attachment.
$attach_id = wp_insert_attachment( $attachment, $filename, $parent_post_id );

// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
//require_once( ABSPATH . 'wp-admin/includes/image.php' );

// Generate the metadata for the attachment, and update the database record.
$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
wp_update_attachment_metadata( $attach_id, $attach_data );
die();
//
//set_post_thumbnail( $parent_post_id, $attach_id );
*/


			if ( count($arrPub)>0 ) {
				add_settings_error( 'wsi_messages', 'wsi_message', __( 'Published images: ', 'woocommerce-sku-images' )  . implode(', ', $arrPub), 'updated' );
			}

			if ( count($arrNoPub)>0 ) {
				add_settings_error( 'wsi_messages', 'wsi_message', __( 'Can\'t publish this images: ', 'woocommerce-sku-images' )  . implode(', ', $arrNoPub), 'error' );
			}


		}

	}


	settings_errors( 'wsi_messages' );


	// Get all products ---------------------------------------------------------------------------

	$arrProd = [];
	$arrProdPub = [];
	$arrProdPen = [];

	$products = wc_get_products( array(
    	'limit' => -1					// All products
	) );

	foreach ($products as $product) {

		$arrProd[ $product->sku ] = $product;

		if ($product->status=='publish') {
			$arrProdPub[ $product->sku ] = $product;
		} else {
			$arrProdPen[ $product->sku ] = $product;
		}

	}


	// * Agrupa les imatges transformades de cada producte ----------------------------------------


	$arrImgOK = array();
	$arrImgKO = array();

	$filesPRO = scandir($dirPRO);

	foreach ($filesPRO as $idx => $proName) {

		if (strpos(strtolower($proName), '.jpg') > 0) {

			$proNameNoExt = str_replace( '.jpg', '', $proName );

			$arrName = explode( "_", $proNameNoExt );

			$proSKU = $arrName[0];

			if (isset($arrProd[ $proSKU ])) {
			
				$proID  = $arrProd[ $proSKU ]->id;

				if (!isset($arrImgOK[ $proID ])) {
					$arrImgOK[ $proID ] = array( );
				}

				$position = count( $arrImgOK[ $proID ] );

/*
$query_images_args = array(
    'post_type'      => 'attachment',
    'post_mime_type' => 'image',
    'post_status'    => 'inherit',
    'posts_per_page' => - 1,
);

$query_images = new WP_Query( $query_images_args );

$images = array();
foreach ( $query_images->posts as $image ) {
    $images[] = wp_get_attachment_url( $image->ID );
}

*/

				$imgs = wp_get_attachment_image( $arrProd[ $proSKU ]->image_id, [50, 50] );

				foreach ($arrProd[ $proSKU ]->gallery_image_ids as $img_id) {
					$imgs .= wp_get_attachment_image( $img_id, [50, 50]  );
				}

				$arrImgOK[ $proID ][] = [
                    'src'      => "$urlPRO/$proName",
                    'name'     => $proName,
                    'position' => $position,
                    'sku'      => $proSKU,
                    'image_id' => $imgs
                ];

			} else {

				$arrImgKO[] = $proName . " - " . __('There are no products widh', 'woocommerce-sku-images') . $proSKU . " SKU";

			}

		}

	}
?>

<div class="wrap">
	
	<h1><?php echo __('Image publication', 'woocommerce-sku-images' ); ?></h1>

	<p><?php echo __('The list shows all the existing images in <i>optimized folder</i>, ready to be published (assigned to an existing SKU product).', 'woocommerce-sku-images' ); ?></p>
	<p><?php echo __('To publish optimized images just check them and press the <i>Publish selected items</i> button (current images assigned to the selected SKU product will be automatically removed).', 'woocommerce-sku-images' ); ?></p>
	<p><?php echo __('To remove optimized images just check them and press the <i>Remove selected items</i> button.', 'woocommerce-sku-images' ); ?></p>

	<h2><?php echo __('Optimized images', 'woocommerce-sku-images'); ?></h2>



	<form id="wsi-form-delete" method="post">

		<input id="wsi-selection" name="wsi-selection" value="" type="hiddenXX" />

		<input id="wsi-action"    name="wsi-action" value="" type="hiddenXX" />

		<div class="tablenav top">

			<div class="alignleft">
				<button id="wsi-publish" type="button" class="button action">
					<?php echo __('Publish selected items', 'woocommerce-sku-images'); ?>
				</button>
			</div>

			<div class="alignleft">
				<button id="wsi-delete" type="button" class="button action">
					<?php echo __('Remove selected items', 'woocommerce-sku-images'); ?>
				</button>
			</div>

			<div class="tablenav-pages">
				<?php echo $page_links; ?>
			</div>

		</div>

		<table class="wp-list-table widefat striped posts">
			<thead>
				<tr>
					<th><?php echo __('All/None', 'woocommerce-sku-images'); ?>
						<input id="wsi-check-all" type="checkbox" /></th>
					<th>SKU</th>
					<th><?php echo __('Product ID', 'woocommerce-sku-images'); ?></th>
					<th><?php echo __('Existing images', 'woocommerce-sku-images'); ?></th>
					<th><?php echo __('New images', 'woocommerce-sku-images'); ?></th>
				</tr>
			</thead>
			<tbody>

<?php

	foreach ($arrImgOK as $idx => $imgOK) {

		$sku = $imgOK[0]['sku'];

		$existing = '';
		foreach ($imgOK as $img) {
			$existing .= (($existing=='')?'':',') . $img['name'];
		}

		echo "<tr>
				<td><input name=\"id_" . $sku . "\" value=\"" . $idx . "\" data-existing=\"" . $existing . "\"
				           type=\"checkbox\" class=\"wsi-check\" /></td>
				<td>" . $sku . "</td>
				<td>$idx</td>
				<td>" . $imgOK[0]['image_id'] . "</td>
				<td>";
		foreach ($imgOK as $img) {
			echo "<img src='" . $img['src'] . "' width='50' />";
		}
		echo "  </td>
		      </tr>";
	}

		echo "</tbody>
			  </table>

			  </form>";


	if (isset($arrWooKO)) {

		echo "<h2>" . __('No existing product SKU', 'woocommerce-sku-images') . "</h2>";

		echo "<pre>
			  <table>";

		echo "  <tr><th colspan='2'><b>" . __('Not published images on WooCommerce', 'woocommerce-sku-images') . "</b></th></tr>";

		foreach ($arrWooKO as $idx => $wooKO) {
			echo "<tr><td>$idx</td><td>$wooKO</td></tr>";
		}
		echo "  <tr><th colspan='2'><b>" . __('Published images on WooCommerce', 'woocommerce-sku-images') . "</b></th></tr>";

		echo "</table>
		      </pre>";

	}

?>

<script type="text/javascript">

$( document ).ready(function() {

    $("#wsi-check-all").toggle(
        function() {
            $('.wsi-check').prop('checked', true);
        },
        function() {
            $('.wsi-check').prop('checked', false);
        }
    );

    $("#wsi-delete").click(function(e) {

        e.preventDefault();

    	var selection = new Array();

        $(".wsi-check:checked").each(function() {
           selection.push( $(this).data('existing') );
        });

        var strselection = selection.join(',');

		if ((strselection!='') && (confirm( "<?php __('Do you want to remove the selected images?', 'woocommerce-sku-images'); ?>"))) {

       		$("#wsi-selection").val( strselection );
			$("#wsi-action").val( 'delete' );
   			$("#wsi-form-delete").submit();

        }
        
    });


    $("#wsi-publish").click(function(e) {

        e.preventDefault();

    	var selection = new Array();

        $(".wsi-check:checked").each(function() {
           selection.push( $(this).val() );
        });

        var strselection = selection.join(',');

        if (strselection!='') {

        	$("#wsi-selection").val( strselection );
			$("#wsi-action").val( 'publish' );
   			$("#wsi-form-delete").submit();

        }

    });


});

</script>
