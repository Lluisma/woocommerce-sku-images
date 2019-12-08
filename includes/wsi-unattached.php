<?php 

	ini_set('max_execution_time', '600');

	// Settings -----------------------------------------------------------------------------------

	$paged = ( $_GET['paged'] ) ? $_GET['paged'] : 1;

	$per_page = is_numeric( get_settings('wsi_field_perpage') )  ?  get_settings('wsi_field_perpage')  :  20;

	$upload_cur = wp_upload_dir(); 
	$upload_dir = $upload_cur['basedir'];
	$upload_url = $upload_cur['baseurl'];


	// POST actions -------------------------------------------------------------------------------

	$action = (isset($_POST['wsi-action'])) ? $_POST['wsi-action'] : '';
	$arrSel = (isset($_POST['wsi-selection'])) ? explode(',', $_POST['wsi-selection']) : null;

	$arrDel   = [];
	$arrNoDel = [];

	$arrAtt    = [];
	$arrNoAtt  = [];

	if ($arrSel) {

		if ($action=='delete') {

			foreach ($arrSel as $srcFile) {

				$subDel   = [];
				$subNoDel = [];

				if (unlink( $upload_dir . $srcFile )) {

					$strDel = $srcFile; $strNoDel = '';

				} else {

					$strDel = ''; $strNoDel = $srcFile;

				}

				$extFile = explode('.', $srcFile)[1];

				$patternSubFiles = $upload_dir . str_replace(".$extFile", "-*.$extFile", $srcFile);

				foreach (glob($patternSubFiles) as $filenameSub) {

					if (unlink( $filenameSub )) {

						$subDel[] = basename($filenameSub);

					} else {

						$subNoDel[] = basename($filenameSub);

					}

				}

				if ($subDel)   { $arrDel[]   = '<br>' . $strDel . '  >>  [ ' . implode(' , ', $subDel) . ' ]';   }
				if ($subNoDel) { $arrNoDel[] = '<br>' . $strNoDel . '  >>  [ ' . implode(' , ', $subNoDel) . ' ]'; }

			}

		} elseif (($action=='add') || ($action=='replace')) {

			// as wp_generate_attachment_metadata() depends on this file
			require_once( ABSPATH . 'wp-admin/includes/image.php' );

			$arrElems  = [];
			$duplicate = false;

			foreach ($arrSel as $filename) {

				$extFile  = pathinfo($filename, PATHINFO_EXTENSION);

				$nameFile = preg_replace('~[-.]~', '_', basename($filename, '.' . $extFile));

				$sku_id   = explode('_', $nameFile);

				$sku      = $sku_id[0];
				$idx      = $sku_id[1];

				if (isset($arrElems[ $sku ][ $idx ])) {

					$duplicate = true;

				} else {

					$arrElems[ $sku ][ $idx ] = $filename;

				}

			}

			if ($duplicate) {

				add_settings_error( 'wsi_messages', 'wsi_message', __( 'Duplicated references set to same index.', 'woocommerce-sku-images' )  . implode(', ', $arrAtt), 'updated' );

			} else {

				foreach ($arrElems as $sku => $arrElem) {

					$subAtt   = [];
					$subNoAtt = [];

					$published = true;

					$product_id = wc_get_product_id_by_sku( $sku );

					$product    = wc_get_product( $product_id );

					$arr_img_id_gallery = $product->get_gallery_image_ids();

					if ($action=='add') {
						
						$hasThumbnail = (get_post_thumbnail_id( $product_id )) ? true : false;
						
					} elseif ($action=='replace') {

						// Remove all existing attachments

						if ($old_thumbnail = get_post_thumbnail_id( $product_id )) {

							$old_att_path = get_attached_file( $old_thumbnail); 

							if (wp_delete_attachment( $old_thumbnail, true )) {

								$arrDel[] = $old_thumbnail;

								unlink($old_att_path);

							} else {

								$arrNoDel[] = $old_thumbnail;

							}

						}

						foreach ($arr_img_id_gallery as $old_img_att) {

							$old_img_path = get_attached_file( $old_img_att);

							if (wp_delete_attachment( $old_img_att, true )) {

								$arrDel[] = $old_img_att;

								unlink($old_img_path);

							} else {

								$arrNoDel[] = $old_img_att;

							}
							
						}				

						$arr_img_id_gallery = [];

						$hasThumbnail       = false;
					}

					foreach ($arrElem as $idx => $filename) {

						// Check the type of file. We'll use this as the 'post_mime_type'.
						$filetype = wp_check_filetype( $filename, null );

						// Prepare an array of post data for the attachment.
						$attachment = array(
						    'post_mime_type' => $filetype['type'],
						    'post_title'     => basename( $filename ),
						    'post_content'   => '',
						    'post_status'    => 'inherit'
						);

						// Insert the attachment
						$attach_id = wp_insert_attachment( $attachment, $upload_dir . '/' . $filename );

						// Generate attachment meta data and create image sub-sizes for images.
						$attach_data = wp_generate_attachment_metadata( $attach_id, $upload_dir . '/' . $filename );

						// Update the metadata database record
						wp_update_attachment_metadata( $attach_id, $attach_data );

						if ($hasThumbnail) {

							$arr_img_id_gallery[] = $attach_id;

						} else {	

							if (set_post_thumbnail( $product_id, $attach_id )) {
								$published    = true;
								$hasThumbnail = true;
								$subAtt[]     = $attach_id;
							} else {
								$subNoAtt[]   = $attach_id;
							}

						} 

					}

					if (update_post_meta($product_id, '_product_image_gallery', implode(',',$arr_img_id_gallery))) {
						$published = true;
						$subAtt = array_merge($subAtt, $arr_img_id_gallery);
					} else {
						$subNoAtt = array_merge($subNoAtt, $arr_img_id_gallery);
					}


					if ($subAtt)   { $arrAtt[]   = '<br>' . $sku . ' >>  [ ' . implode(' , ', $subAtt) . ' ]';   }
					if ($subNoAtt) { $arrNoAtt[] = '<br>' . $sku . ' >>  [ ' . implode(' , ', $subNoAtt) . ' ]'; }


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

		if ( count($arrDel)>0 ) {
			add_settings_error( 'wsi_messages', 'wsi_message1', __( 'Deleted images: ', 'woocommerce-sku-images' )  . implode(', ', $arrDel), 'updated' );
		}

		if ( count($arrNoDel)>0 ) {
			add_settings_error( 'wsi_messages', 'wsi_message2', __( 'Can\'t delete this images: ', 'woocommerce-sku-images' )  . implode(', ', $arrNoDel), 'error' );
		}

		if ( count($arrAtt)>0 ) {
			add_settings_error( 'wsi_messages', 'wsi_message3', __( 'Attached images: ', 'woocommerce-sku-images' )  . implode(', ', $arrAtt), 'updated' );
		}

		if ( count($arrNoAtt)>0 ) {
			add_settings_error( 'wsi_messages', 'wsi_message4', __( 'Can\'t attach this images: ', 'woocommerce-sku-images' )  . implode(', ', $arrNoAtt), 'error' );
		}

	}

	settings_errors( 'wsi_messages' );





	// Get all attachments ------------------------------------------------------------------------

	$arrAttach = [];

	$args = array('post_type'=>'attachment', 'numberposts'=>-1, 'post_status'=>null, 'post_parent' => null );

	$attachments = get_posts($args);

    if ($attachments) {

		foreach($attachments as $attachment){

			$fileAtt = get_attached_file( $attachment->ID );
			$nomAtt  = basename($fileAtt);

			$sku_id  = wsi_normTitle($nomAtt);

			$arrAttach[ $sku_id[0] ] = $fileAtt;

		}
	}


	// Get all image products ('SKU_index*.jpg') from upload folders ------------------------------

	function getDirContents( $dir, $upload_dir, $upload_url, $arrAtt, &$results = array() ){

	    $files = scandir($dir);

	    foreach ($files as $key => $value) {

	        $path = realpath($dir.DIRECTORY_SEPARATOR.$value);

	        if (!is_dir($path)) {

	        	$arrFile = wsi_normTitle($value);

	        	$skuFile = $arrFile[1];
	        	$idxFile = $arrFile[2];

	        	if (is_numeric($skuFile)) {

	        		// Ignore resized images

	        		if (strpos($value, 'x') === false) {

	        			$guidFile = str_replace($upload_dir, $upload_url, $path);

	        			if (is_numeric($idxFile)) {

							if (!isset($arrAtt[ $arrFile[0] ]) || ($arrAtt[ $arrFile[0] ]!=$path )) {

								if (!isset($results[ $arrFile[0] ])) {
	        						$results[ $arrFile[0] ] = $guidFile;

	        					}

							}

						}

					}
	        	}

	        } else if($value != "." && $value != "..") {

				if (is_numeric($value)) {

	            	getDirContents($path, $upload_dir, $upload_url, $arrAtt, $results );

				}

	        }
	    }

	    return $results;
	}


	$arrUploads = getDirContents( $upload_dir, $upload_dir, $upload_url, $arrAttach );


	ksort($arrUploads);


	// Pager --------------------------------------------------------------------------------------


	$big = 999999999; // need an unlikely integer

	$total_pages = ceil( count($arrUploads) / $per_page );
	 
	$page_links = paginate_links( array(
	    'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
	    'format' => '?paged=%#%',
	    'current' => max( 1, $paged ),
	    'total' => $total_pages,
	) );


?>

<div class="wrap">

	<h1>WooCommerce SKU Images : <?php echo __('Unattached images on <i>uploads</i> folder', 'woocommerce-sku-images' ); ?></h1>

	<div class="wsi-callout">
		<p><?php echo __('There may be images with SKU_index formated name on <i>wp-content/uploads</i> folder that hasn\'t been attached to correspondent SKU product. You can perform the following actions:', 'woocommerce-sku-images' ); ?></p>
		<a id="wsi-info" nohref>
			<span class="dashicons dashicons-info"></span>
			<?php echo __('Read more'); ?>...
		</a>
		<p class="wsi-info">
			<b><?php echo __('Attach & Add selected images', 'woocommerce-sku-images'); ?></b> : 
			<?php echo __('Add the selected images (first column <i>checkboxes</i>) to the existing gallery product images (even the first one as thumbnail if it does not exist).', 'woocommerce-sku-images' ); ?>
		</p>
		<p class="wsi-info">
			<b><?php echo __('Attach & Replace with selected images', 'woocommerce-sku-images'); ?></b> : 
			<?php echo __('Remove the existing product images (thumbnail and gallery) and attach the selected ones (first column <i>checkboxes</i>).', 'woocommerce-sku-images' ); ?>
		</p>
		<p class="wsi-info">
			<b><?php echo __('Remove selected images', 'woocommerce-sku-images'); ?></b> : 
			<?php echo __('Remove the selected ones on last column <i>checkboxes</i> from <i>upload</i> folder.', 'woocommerce-sku-images' ); ?>
		</p>
	</div>


	<hr class="wsi_hr">


	<h2><?php echo ( count($arrUploads) ); ?> <?php echo __('Unattached images', 'woocommerce-sku-images'); ?></h2>


	<form id="wsi-form-unattached" method="post">

		<input id="wsi-selection" name="wsi-selection" value="" type="hidden" />
		<input id="wsi-action"    name="wsi-action" value="" type="hidden" />

		<div class="tablenav top">

			<div class="alignleft">
				<select id="wsi-option">
					<option value="add"><?php echo __('Attach & Add selected images', 'woocommerce-sku-images'); ?></option>
					<option value="replace"><?php echo __('Attach & Replace with selected images', 'woocommerce-sku-images'); ?></option>
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
					<th><?php echo __('Attach'); ?> <input id="wsi-check-att" type="checkbox" /></th>
					<th><?php echo __('File'); ?></th>
					<th>GUID</th>
					<th>SKU</th>
					<th><?php echo __('Product attachments', 'woocommerce-sku-images'); ?></th>
					<th><?php echo __('Comment'); ?></th>
					<th><?php echo __('Remove'); ?> <input id="wsi-check-del" type="checkbox" /></th>
				</tr>
			</thead>
			<tbody>
<?php

	$numFiles = 0;

	$endPage = $paged * $per_page;
	$iniPage = $endPage - $per_page;

	foreach($arrUploads as $sku_id => $upload) {

		if (($numFiles >= $iniPage) && ($numFiles < $endPage)) {

			$sku = explode('_',$sku_id)[0];
			$idx = explode('_',$sku_id)[1];
			$basename = basename($upload);
			$pathname = str_replace($upload_url, '', $upload);

			$product_id = wc_get_product_id_by_sku( $sku );

			$srcFile = str_replace($upload_url, '', $upload);

			if ($product_id) {

				$images = '';

				$image_id = get_post_thumbnail_id( $product_id );

				if ($thumbnail = wp_get_attachment_image_src( $image_id ) ) {
			    	$images = '<img src="' . $thumbnail[0] . '" width="50" title="' . $image_id . ' : ' . $thumbnail[0] . '" >';
			    }

				$product = wc_get_product( $product_id );
 
        		$images_ids = $product->get_gallery_image_ids();

        		foreach($images_ids as $image_id) {

					if ($thumbnail = wp_get_attachment_image_src( $image_id )) {
						$images .= '<img src="' . $thumbnail[0] . '" width="50" title="' . $image_id . ' : ' . $thumbnail[0] . '" />';
					}

        		}

				if ($images) {

					echo '<tr>
							<td><input data-src="' . $srcFile . '" data-sku="' . $sku . '" data-index="' . $idx . '"
							           type="checkbox" class="wsi-check-att"/></td>
							<td>' . $pathname . '</td>
							<td><img src="' . $upload . '" width="50" />
							<td>' . $sku . '</td>
							<td>' . $images . '</td>
							<td></td>
							<td><input data-src="' . $srcFile . '" type="checkbox" class="wsi-check-del"/></td>';

				} else {

					echo '<tr>
							<td><input data-src="' . $srcFile . '" data-sku="' . $sku . '" data-index="' . $idx . '"
							           type="checkbox" class="wsi-check-att"/></td>
							<td>' . $pathname . '</td>
							<td><img src="' . $upload . '" width="50" />
							<td>' . $sku . '</td>
							<td>' . $images . '</td>
							<td></td>
							<td><input data-src="' . $srcFile . '" type="checkbox" class="wsi-check-del"/></td>';

				}

			} else {

				echo '<tr>
						<td></td>
						<td>' . $pathname . '</td>
						<td><img src="' . $upload . '" width="50" />
				        <td>' . $sku . '</td>
						<td>' . ($arrImages[ $sku ]) . '</td>
					  	<td>' . __('There are no products with this SKU','woocommerce-sku-images') . '</td>
					  	<td><input data-src="' . $srcFile . '" type="checkbox" class="wsi-check-del"/></td>';

			}

		}

		$numFiles++;

	}

?>

			</tbody>
		</table>

		<div class="tablenav-pages">
			<?php echo $page_links; ?>
		</div>

	</form>

</div>

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
					$("#wsi-form-unattached").submit();
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
