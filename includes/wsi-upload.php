<?php

	ini_set('max_execution_time', '600');

	// Settings -----------------------------------------------------------------------------------

	$paged = ( $_GET['paged'] ) ? $_GET['paged'] : 1;

	$per_page = is_numeric( get_settings('wsi_field_perpage') )  ?  get_settings('wsi_field_perpage')  :  20;
	
	$img_size = is_numeric( get_settings('wsi_field_imgsize') )  ?  get_settings('wsi_field_imgsize')  :  600;

	$dirPRE = ABSPATH . get_option('wsi_field_dirpre');

	if (get_option('wsi_field_dirpre')=='') {
		add_settings_error( 'wsi_messages', 'wsi_message', __( 'Not defined preload folder on plugin settings.', 'woocommerce-sku-images' ), 'error' );
	} else {
		if (!is_dir(ABSPATH . get_option('wsi_field_dirpre'))) {
			add_settings_error( 'wsi_messages', 'wsi_message', __( 'The preload folder defined on plugin settings does not exist.', 'woocommerce-sku-images' ), 'error' );
			$dirPRE = ABSPATH;
		}
	} 

	$urlPRE = get_site_url() . '/' . get_option( 'wsi_field_dirpre' );

	$upload_cur = wp_upload_dir(); 
	$upload_dir = $upload_cur['basedir'];

	// POST actions -------------------------------------------------------------------------------

	$action = (isset($_POST['wsi-action'])) ? $_POST['wsi-action'] : '';
	$arrSel = (isset($_POST['wsi-selection'])) ? explode(',', $_POST['wsi-selection']) : null;

	$arrDel   = [];
	$arrNoDel = [];
	$arrUpl   = [];
	$arrNoUpl = [];

	if ($arrSel) {

		if ($action=='delete') {

			foreach ($arrSel as $name) {

				if (unlink( $dirPRE . '/' . $name )) {
			
					$arrDel[] = $name;

				} else {

					$arrNoDel[] = $name;

				}

			}

		} elseif (($action=='add') || ($action=='replace')) {

			// as wp_generate_attachment_metadata() depends on this file
			require_once( ABSPATH . 'wp-admin/includes/image.php' );

			$arrElems  = [];
			$duplicate = false;

			foreach ($arrSel as $filename) {

				$extFile = pathinfo($filename, PATHINFO_EXTENSION);
				$sku_id  = wsi_normTitle( basename($filename, '.' . $extFile) );

				$sku     = $sku_id[1];
				$idx     = $sku_id[2];

				if (isset($arrElems[ $sku ][ $idx ])) {

					$duplicate = true;

				} else {

					$arrElems[ $sku ][ $idx ] = $filename;

				}

			}

			if ($duplicate) {

				add_settings_error( 'wsi_messages', 'wsi_message0', __( 'Duplicated references set to same index.', 'woocommerce-sku-images' )  . implode(', ', $arrUpl), 'updated' );

			} else {

				foreach ($arrElems as $sku => $arrElem) {

					$subUpl   = [];
					$subNoUpl = [];
					$subOpt   = [];

					$published = true;

					$product_id  = wc_get_product_id_by_sku( $sku );

					$product     = wc_get_product( $product_id );

					$gallery_ids = $product->gallery_image_ids;

					if ($action=='add') {
			
						$hasThumbnail = (get_post_thumbnail_id( $product_id )) ? true : false;
			
					} elseif ($action=='replace') {

						// Remove all existing attachments

						$arrImages = [];

						if ($image_id = get_post_thumbnail_id( $product_id )) {
							$arrImages[] = $image_id;
						}
						if ($gallery_ids) { 
							$arrImages = array_merge( $arrImages, $gallery_ids );
						}
	
						foreach($arrImages as $image_id) {

							$image_path = get_attached_file( $image_id ); 

							if (wp_delete_attachment( $image_id, true )) {

								$arrDel[] = $image_id;

								if (file_exists($image_path)) {
									unlink($image_path);
								}

							} else {

								$arrNoDel[] = $image_id;

							}

						}

						$gallery_ids  = [];
						$hasThumbnail = false;

					}

					foreach ($arrElem as $idx => $filename) {

						$filePath = $dirPRE . '/' . $filename;

						$newPath = $upload_dir . '/' . date('Y') . '/' . date('m') . '/' . $filename;

						$src = imagecreatefromjpeg( $filePath );
		
						$fileWidth  = imagesx ( $src );
						$fileHeight = imagesy ( $src );
		
						if ($img_size) {

							// Optimize image -----------------------------------------------------

							$ratio = $fileWidth / $fileHeight;
		
							if ($ratio > 1) {
								$newWidth  = ( $fileWidth > $img_size ) ? $img_size : $fileWidth;
								$newHeight = $newWidth / $ratio;
							} else {
								$newHeight = ( $fileHeight > $img_size ) ? $img_size : $fileWidth;
								$newWidth  = $newHeight * $ratio;
						
							}

						} else {

							$newHeight = $fileHeight;
							$newWidth  = $fileWidth;

						}
		
						$dst = imagecreatetruecolor( $newWidth, $newHeight );
		
						$status = imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $fileWidth, $fileHeight);
		
						if ($status === FALSE) {
		
							$arrNoUpl[] = $filename;
		
						} else {

							$subOpt[] = $filename;

							unlink( $filePath );

							imagejpeg($dst, $newPath);
							
							imagedestroy( $src );
			
							imagedestroy( $dst );

							// Attach image -------------------------------------------------------

							$filetype = wp_check_filetype( $newPath, null );
				
							// Prepare an array of post data for the attachment.
							$attachment = array(
								'post_mime_type' => $filetype['type'],
								//'post_title'     => basename( $filename ),
								'post_title'     => basename($filename, '.' . $extFile),
								'post_content'   => '',
								'post_status'    => 'inherit'
							);
				
							// Insert the attachment
							$attach_id = wp_insert_attachment( $attachment, $newPath );
				
							// Generate attachment meta data and create image sub-sizes for images.
							$attach_data = wp_generate_attachment_metadata( $attach_id, $newPath );
				
							// Update the metadata database record
							wp_update_attachment_metadata( $attach_id, $attach_data );
				
							if ($hasThumbnail) {
				
								$gallery_ids[] = $attach_id;
				
							} else {	
				
								if (set_post_thumbnail( $product_id, $attach_id )) {
									$published    = true;
									$hasThumbnail = true;
									$subUpl[]     = $attach_id;
								} else {
									$subNoUpl[]   = $attach_id;
								}
				
							} 

						}
			
					}

					if (update_post_meta($product_id, '_product_image_gallery', implode(',',$gallery_ids))) {
						$published = true;
						$subUpl = array_merge($subUpl, $gallery_ids);
					} else {
						$subNoUpl = array_merge($subNoUpl, $gallery_ids);
					}
			
			
					if ($subUpl)   { $arrUpl[]   = '<br>SKU ' . $sku . ' :  [ ' . implode(' , ', $subOpt ) .  ' ] ( ' . implode(' , ', $subUpl) . ' )';   }
					if ($subNoUpl) { $arrNoUpl[] = '<br>SKU ' . $sku . ' :  [ ' . implode(' , ', $subNoUpl) . ' ]'; }
			
			
					// Sets status to 'publish' if any image has been attached
			
					if ($published) {
						$curr_product = array();
						$curr_product['ID'] = $product_id;
						$curr_product['post_status'] = 'publish';

						wp_update_post( $curr_product );
					}

					

				}

			}

		}

	}


	if ( count($arrDel)>0 ) {
		add_settings_error( 'wsi_messages', 'wsi_message1', __( 'Deleted images: ', 'woocommerce-sku-images' )  . implode(', ', $arrDel), 'updated' );
	}

	if ( count($arrNoDel)>0 ) {
		add_settings_error( 'wsi_messages', 'wsi_message2', __( 'Can\'t delete this images: ', 'woocommerce-sku-images' )  . implode(', ', $arrNoDel), 'error' );
	}

	if ( count($arrUpl)>0 ) {
		add_settings_error( 'wsi_messages', 'wsi_message3', __( 'Upload images: ', 'woocommerce-sku-images' )  . implode(', ', $arrUpl), 'updated' );
	}

	if ( count($arrNoUpl)>0 ) {
		add_settings_error( 'wsi_messages', 'wsi_message4', __( 'Can\'t upload this images: ', 'woocommerce-sku-images' )  . implode(', ', $arrNoUpl), 'error' );
	}

	settings_errors( 'wsi_messages' );


	
	// Get all products ---------------------------------------------------------------------------

	$arrProd = [];

	$products = wc_get_products( array(
    	'limit' => -1
	) );

	foreach ($products as $product) {

		$arrImages = [];

		if ($image_id = $product->image_id) {
			$arrImages[] = $image_id;
		}
		if ($gallery_ids = $product->gallery_image_ids) { 
			$arrImages = array_merge( $arrImages, $gallery_ids );
		}

		$arrProd[ $product->sku ] = $arrImages;

	}


	// * Get all SKU_index formatted images on preload folder -------------------------------------

	$numFiles = 0;
	$tabFiles = '';

	$endPage = $paged * $per_page;
	$iniPage = $endPage - $per_page;

	$filesPRE = scandir($dirPRE);

	foreach ($filesPRE as $preName) {

		if (pathinfo($preName, PATHINFO_EXTENSION) == 'jpg') {

			if (($numFiles >= $iniPage) && ($numFiles < $endPage)) {

				$sku_id = wsi_normTitle( basename($preName, '.jpg') );

				$sku = $sku_id[1];

				$filePath = $dirPRE . '/' . $preName;
				$size = getimagesize($filePath);

				$tabFiles .= '<tr>
							<td>';

				if (isset($arrProd[ $sku ])) {

					$tabFiles .= '<input value="' . $preName . '" data-sku="' . $sku . '"
										type="checkbox" class="wsi-check-upl" />';
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

					}

				} else {

					$tabFiles .= '<td></td><td>' . __('There are no products with this SKU', 'woocommerce-sku-images') . '</td>';

				}

				$tabFiles.= '</td>
							<td><input value="' . $preName . '" type="checkbox" class="wsi-check-del" /></td>
						</tr>';

			}

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
	
	<h1>WooCommerce SKU Images : <?php echo __('Pending uploads', 'woocommerce-sku-images' ); ?></h2>

	<div class="wsi-callout">
		<p><?php echo __('The list shows all the existing images in the preload folder (defined on settings page) and checks if each image name is SKU_index formatted.', 'woocommerce-sku-images' ); ?></p>
		<p><?php echo __('Images with a correct SKU could be renamed with the normalized name (removing blank spaces, dashes, parenthesis, etc...), resized and attached to the correspondent SKU product. You can perform the following actions:', 'woocommerce-sku-images' ); ?></p>
		<a id="wsi-info" nohref>
			<span class="dashicons dashicons-info"></span>
			<?php echo __('Read more'); ?>...
		</a>
		<p class="wsi-info">
			<b><?php echo __('Upload & Add selected images', 'woocommerce-sku-images'); ?></b> :
			<?php echo __('Add the selected images (first column <i>checkboxes</i>) to the existing gallery product images (even the first one as thumbnail if it does not exist).', 'woocommerce-sku-images' ); ?>
		</p>
		<p class="wsi-info">
			<b><?php echo __('Upload & Replace with selected images', 'woocommerce-sku-images'); ?></b> : 
			<?php echo __('Remove the existing product images (thumbnail and gallery) and attach the selected ones (first column <i>checkboxes</i>).', 'woocommerce-sku-images' ); ?>
		</p>
		<p class="wsi-info">
			<b><?php echo __('Remove selected images from upload folder', 'woocommerce-sku-images'); ?></b> : 
			<?php echo __('Remove the selected ones on last column <i>checkboxes</i>.', 'woocommerce-sku-images' ); ?>
		</p>
	</div>
	
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
						<input id="wsi-check-upl" type="checkbox" /></th>
					<th><?php echo __('Original Filename', 'woocommerce-sku-images'); ?></th>
					<th><?php echo __('Normalized Filename', 'woocommerce-sku-images'); ?></th>
					<th><?php echo __('Image'); ?></th>
					<th><?php echo __('Width'); ?></th>
					<th><?php echo __('Height'); ?></th>
					<th>SKU</th>
					<th><?php echo __('Product attachments', 'woocommerce-sku-images'); ?></th>
					<th><?php echo __('Remove'); ?> 
						<input id="wsi-check-del" type="checkbox" /></th>
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

    $("#wsi-check-upl").toggle(
        function() {
            $('.wsi-check-upl').prop('checked', true);
        },
        function() {
            $('.wsi-check-upl').prop('checked', false);
        }
    );

    $(".wsi-check-upl").click( function() {

    	var checkSKU   = $(this).data('sku');
    	var checkState = $(this).prop('checked');
    	$('.wsi-check-upl[data-sku="' + checkSKU + '"]').prop('checked', checkState);

    });


	$("#wsi-button").click(function(e) {

        e.preventDefault();

        var action    = $("#wsi-option").val();
        var selection = new Array();

		$("#wsi-action").val( action );

		if (action=='delete') {
		    $(".wsi-check-del:checked").each(function() {
        	   selection.push( $(this).val() );
        	});
        	var msg_confirm = "<?php echo __('Do you want to remove the selected images?', 'woocommerce-sku-images'); ?>";
		} else if (action=='add') {
	        $(".wsi-check-upl:checked").each(function() {
    	       selection.push( $(this).val() );
        	});
        	var msg_confirm = "<?php echo __('Selected images will be added to the product\'s existing ones. \nDo you agree?', 'woocommerce-sku-images'); ?>";
		} else if (action=='replace') {
	        $(".wsi-check-upl:checked").each(function() {
				selection.push( $(this).val() );
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

	$("#wsi-info").click( function(e) {
		$(".wsi-info").toggle();
	})

});

</script>