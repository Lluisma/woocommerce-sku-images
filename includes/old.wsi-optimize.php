<?php


	$dirPRE = ABSPATH . get_option( 'wsi_field_dirpre' );

	$urlPRE = get_site_url() . '/' . get_option( 'wsi_field_dirpre' );

	define('MINSIZE', 600);


	// Captura dades POST si hi ha imatges precarregades per optimitzar o eliminar ----------------

	$action = (isset($_POST['wsi-action'])) ? $_POST['wsi-action'] : '';
	$arrSel = (isset($_POST['wsi-selection'])) ? explode(',', $_POST['wsi-selection']) : null;


	if ($arrSel) {

		if ($action=='delete') {

			// Esborra les imatges seleccionades

			$arrDel   = [];
			$arrNoDel = [];

			foreach ($arrSel as $name) {

				if (unlink( $dirPRE . '/' . $name )) {
			
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

		} elseif ($action=='optimize') {

			$arrOpt   = [];
			$arrNoOpt = [];

			foreach ($arrSel as $name) {


				$normName = str_replace( ' ',  '_', $name );
				$normName = str_replace( '(',  '_', $normName );
				$normName = str_replace( ')',  '',  $normName );

				$normName = str_replace( '__', '_', $normName );

				$newPath  = $dirPRO . '/' . $normName;

				// Dimensions de la imatge

				$filePath = $dirPRE . '/' . $name;

				$src = imagecreatefromjpeg( $filePath );

				$fileWidth  = imagesx ( $src );
				$fileHeight = imagesy ( $src );

				$ratio = $fileWidth / $fileHeight;

				if ($ratio > 1) {
					$newWidth  = ($fileWidth>MINSIZE) ? MINSIZE : $fileWidth;
	    			$newHeight = $newWidth / $ratio;
				} else {
					$newHeight = ($fileHeight>MINSIZE) ? MINSIZE : $fileWidth;
	    			$newWidth  = $newHeight * $ratio;
				}

				$dst = imagecreatetruecolor( $newWidth, $newHeight );

				$status = imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $fileWidth, $fileHeight);

				if ($status === FALSE) {

	    			$arrNoOpt[] = $name;

				} else {

					imagejpeg($dst, $newPath);
					unlink( $filePath );
					$arrOpt[] = $name;

				}

				imagedestroy( $src );

				imagedestroy( $dst );

			}

			if ( count($arrOpt)>0 ) {
				add_settings_error( 'wsi_messages', 'wsi_message', __( 'Optimized images: ', 'woocommerce-sku-images' )  . implode(', ', $arrOpt), 'updated' );
			}

			if ( count($arrNoOpt)>0 ) {
				add_settings_error( 'wsi_messages', 'wsi_message', __( 'Can\'t optimize this images: ', 'woocommerce-sku-images' )  . implode(', ', $arrNoOpt), 'error' );
			}

		}

	}

	settings_errors( 'wsi_messages' );


	
	// Get all products ---------------------------------------------------------------------------

	$arrProd = [];

	$products = wc_get_products( array(
    	'limit' => -1
	) );

	foreach ($products as $product) {

//		$arrProd[ $product->sku ] = true;

		$arrImages = [];

		if ($image_id = $product->image_id) {
			$arrImages[] = $image_id;
		}
		if ($gallery_ids = $product->gallery_image_ids) { 
			$arrImages = array_merge( $arrImages, $gallery_ids );
		}

		$arrProd[ $product->sku ] = $arrImages;

	}


	// * Get all SKU_index formatted images on preload folder and group them by SKU ---------------


	$numFiles = 0;
	$tabFiles = '';

	$filesPRE = scandir($dirPRE);

	foreach ($filesPRE as $idx => $preName) {

		if (pathinfo($preName, PATHINFO_EXTENSION) == 'jpg') {

			$sku_id = wsi_normTitle( basename($preName, '.jpg') );

			$sku = $sku_id[1];
$sku=2398;
			$filePath = $dirPRE . '/' . $preName;
			$size = getimagesize($filePath);

			$tabFiles .= '<tr>
						<td>';

			if (isset($arrProd[ $sku ])) {
				$tabFiles .= '<input value="' . $preName . '" type="checkbox" class="wsi-check-opt" />';
			}

			$tabFiles .= '	</td>
							<td>' . $preName . '</td>
							<td>' . $sku_id[0] . '.jpg</td>
							<td><img src="' . $urlPRE . '/' . $preName . '" width="50" /></td>
							<td>' . $size[0] . '</td>
							<td>' . $size[1] . '</td>';

			if (isset($arrProd[ $sku ])) {
				$tabFiles .= '<td>' . $sku . '</td>
				    		<td>';

	       		foreach($arrProd[ $sku ] as $image_id) {

					if ($thumbnail = wp_get_attachment_image_src( $image_id )) {
						$tabFiles .= '<img src="' . $thumbnail[0] . '" width="50" title="' . $image_id . ' : ' . $thumbnail[0] . '" />';
					}

        		}m

			} else {

				$tabFiles .= __('There are no products widh ', 'woocommerce-sku-images') . $proSKU . " SKU";

			}

			$tabFiles.= '</td>
						<td><input value="' . $preName . '" type="checkbox" class="wsi-check-del" /></td>
					</tr>';
			$numFiles++;

		}

	}

	// Pager --------------------------------------------------------------------------------------

	$big = 999999999; // need an unlikely integer

	$total_pages = ceil( $numFiles / $per_page );

	$page_links = paginate_links( array(
	    'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
	    'format' => '?paged=%#%',
	    'current' => max( 1, $paged ),
	   	'total' => $total_pages,
	) );

?>

<div class="wrap">
	
	<h1>WooCommerce SKU Images : <?php echo __('Publish images', 'woocommerce-sku-images' ); ?></h2>

	<p><?php echo __('The list shows all the existing images in the preload folder (defined on settings page) and checks if each image name is SKU_index formatted.', 'woocommerce-sku-images' ); ?></p>

	<p><?php echo __('Images with a correct SKU could be renamed with the normalized name (removing blank spaces, dashes, parenthesis, etc...), resized and attached to the correspondent SKU product.', 'woocommerce-sku-images' ); ?></p>

	<p><?php echo __('You can perform the following actions:', 'woocommerce-sku-images' ); ?></p>

	<p>
		<b><?php echo __('Upload & Add selected images', 'woocommerce-sku-images'); ?></b><br>
		<?php echo __('Add the selected images (first column <i>checkboxes</i>) to the existing gallery product images (even the first one as thumbnail if it does not exist).', 'woocommerce-sku-images' ); ?>
	</p>
		
	<p>
		<b><?php echo __('Upload & Replace with selected images', 'woocommerce-sku-images'); ?></b><br>
		<?php echo __('Remove the existing product images (thumbnail and gallery) and attach the selected ones (first column <i>checkboxes</i>).', 'woocommerce-sku-images' ); ?>
	</p>
		
	<p>
		<b><?php echo __('Remove selected images from upload folder', 'woocommerce-sku-images'); ?></b><br>
		<?php echo __('Remove the selected ones on last column <i>checkboxes</i>.', 'woocommerce-sku-images' ); ?>
	</p>

	
	<h2><?php echo $numFiles; ?> <?php echo __('Pending images', 'woocommerce-sku-images'); ?></h2>


	<form id="wsi-form-upload" method="post">

		<input id="wsi-selection" name="wsi-selection" value="" type="hidden" />

		<input id="wsi-action"    name="wsi-action" value="" type="hidden" />

		<div class="tablenav top">

			<div class="alignleft">
				<select id="wsi-option">
					<option value="add"><?php echo __('Upload & Add selected images', 'woocommerce-sku-images'); ?></option>
					<option value="replace"><?php echo __('Upload & Replace with selected images', 'woocommerce-sku-images'); ?></option>
					<option value="delete"><?php echo __('Remove selected images from upload folder', 'woocommerce-sku-images'); ?></option>
				</select>

				<button id="wsi-button" type="button" class="button action">
					<?php echo __('Execute', 'woocommerce-sku-images'); ?>
				</button>
			</div>

			<div class="tablenav-pages">
				<?php echo $page_links; ?>
			</div>

		</div>

		<table class="wp-list-table widefat striped posts">
			<thead>
				<tr>
					<th><?php echo __('Upload'); ?>
						<input id="wsi-opt-all" type="checkbox" /></th>
					<th><?php echo __('Original Filename', 'woocommerce-sku-images'); ?></th>
					<th><?php echo __('Normalized Filename', 'woocommerce-sku-images'); ?></th>
					<th><?php echo __('Image'); ?></th>
					<th><?php echo __('Width'); ?></th>
					<th><?php echo __('Height'); ?></th>
					<th>SKU</th>
					<th><?php echo __('Product attachments', 'woocommerce-sku-images'); ?></th>
					<th><?php echo __('Remove'); ?> 
						<input id="wsi-del-all" type="checkbox" /></th>
				</tr>
			</thead>
			<tbody>
<?php
				echo $tabFiles;
?>
		</tbody>
	</table>

  </form>



<script type="text/javascript">

$( document ).ready(function() {


    $("#wsi-check-del").toggle(
        function() {
            $('.wsi-check-del').prop('checked', true);
        },
        function() {
            $('.wsi-check-del').prop('checked', false);
        }
    );

    $("#wsi-check-att").toggle(
        function() {
            $('.wsi-check-att').prop('checked', true);
        },
        function() {
            $('.wsi-check-att').prop('checked', false);
        }
    );

    $(".wsi-check-att").click( function() {

    	var checkSKU   = $(this).data('sku');
    	var checkState = $(this).prop('checked');
    	$('.wsi-check-att[data-sku="' + checkSKU + '"]').prop('checked', checkState);

    });


	$("#wsi-button").click(function(e) {

        e.preventDefault();

        var action    = $("#wsi-option").val();
        var selection = new Array();

		$("#wsi-action").val( action );

		if (action=='delete') {
		    $(".wsi-check-del:checked").each(function() {
        	   selection.push( $(this).data('src') );
        	});
        	var msg_confirm = "<?php echo __('Do you want to remove the selected images?', 'woocommerce-sku-images'); ?>";
		} else if (action=='add') {
	        $(".wsi-check-att:checked").each(function() {
    	       selection.push( $(this).data('src'));
        	});
        	var msg_confirm = "<?php echo __('Selected images will be added to the product\'s existing ones. \nDo you agree?', 'woocommerce-sku-images'); ?>";
		} else if (action=='replace') {
	        $(".wsi-check-att:checked").each(function() {
    	       selection.push( $(this).data('src'));
        	});
        	var msg_confirm = "<?php echo __('Existing product images will be removed. \nDo you agree?', 'woocommerce-sku-images'); ?>";
		} else {
			alert( "<?php echo __('Select any option', 'woocommerce-sku-images'); ?>" )
		}

		if (action) {
			if (selection.length>0){
				$("#wsi-selection").val( selection.join(',') );
				if (confirm( msg_confirm )) {
					$("#wsi-form-upload").submit();
				}
		    } else {
		 		alert( "<?php echo __('No selected items', 'woocommerce-sku-images'); ?>" )   	
		    }
		}

    });

});

</script>