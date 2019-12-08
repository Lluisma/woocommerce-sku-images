<?php 

	// Settings -----------------------------------------------------------------------------------

	$paged = ( $_GET['paged'] ) ? $_GET['paged'] : 1;

	$per_page = is_numeric( get_settings('wsi_field_perpage') )  ?  get_settings('wsi_field_perpage')  :  20;

	

	// POST actions -------------------------------------------------------------------------------

	$action = (isset($_POST['wsi-action'])) ? $_POST['wsi-action'] : '';
	$arrSel = (isset($_POST['wsi-selection'])) ? explode(',', $_POST['wsi-selection']) : null;

	$arrDel   = [];
	$arrNoDel = [];

	$arrAtt    = [];
	$arrNoAtt  = [];


	if ($arrSel) {

		if ($action=='delete') {

			// Delete attachments and related images

			foreach ($arrSel as $id) {

				$old_id_path = get_attached_file( $id); 

				if (wp_delete_attachment( $id, true )) {

					$arrDel[] = $id;

					unlink($old_id_path);

				} else {

					$arrNoDel[] = $id;

				}

			}

		} elseif (($action=='add') || ($action=='replace')) {

			$arrElems  = [];
			$duplicate = false;

			foreach ($arrSel as $elem) {

				$arrObj = explode('-', $elem);

				$idAtt  = $arrObj[0];
				$skuAtt = $arrObj[1];
				$idxAtt = $arrObj[2];

				if (isset($arrElems[ $skuAtt ][ $idxAtt ])) {
					$duplicate = true;

				} else {

					$arrElems[ $skuAtt ][ $idxAtt ] = $idAtt;

				}

			}

			if ($duplicate) {

				add_settings_error( 'wsi_messages', 'wsi_message0', __( 'Duplicated elements', 'woocommerce-sku-images' )  . implode(', ', $arrAtt), 'updated' );

			} else {		

				foreach ($arrElems as $skuAtt => $arrElem) {

					$subAtt   = [];
					$subNoAtt = [];

					$published   = true;

					$product_id  = wc_get_product_id_by_sku( $skuAtt );

					$product     = wc_get_product( $product_id );

					$gallery_ids = $product->get_gallery_image_ids();


					if ($action=='add') {
						
						$hasThumbnail = (get_post_thumbnail_id( $product_id )) ? true : false;

					} elseif ($action=='replace') {

						// Remove all existing attachments

						if ($old_thumbnail = get_post_thumbnail_id( $product_id )) {

							$gallery_ids = array_merge( [ $old_thumbnail ], $gallery_ids );

						}

						foreach ($gallery_ids as $old_img_att) {

							$old_img_path = get_attached_file( $old_img_att);

							if (wp_delete_attachment( $old_img_att, true )) {

								$arrDel[] = $old_img_att;

								unlink($old_img_path);

							} else {

								$arrNoDel[] = $old_img_att;

							}
							
						}				

						$gallery_ids = [];

						$hasThumbnail = false;
					}


					foreach ($arrElem as $attach_id) {

						if ($hasThumbnail) {

							$gallery_ids[] = $attach_id;

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


					if (update_post_meta($product_id, '_product_image_gallery', implode(',',$gallery_ids))) {
						$published = true;
						$subAtt = array_merge($subAtt, $gallery_ids);
					} else {
						$subNoAtt = array_merge($subNoAtt, $gallery_ids);
					}


					if ($subAtt)   { $arrAtt[]   = '<br>' . $skuAtt . ' >>  [ ' . implode(' , ', $subAtt) . ' ]';   }
					if ($subNoAtt) { $arrNoAtt[] = '<br>' . $skuAtt . ' >>  [ ' . implode(' , ', $subNoAtt) . ' ]'; }

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



	// Get all products and the related image ids -------------------------------------------------

	$arrProd = [];

	$arrImages = [];

	$products = wc_get_products( array(
    	'limit' => -1					// All products
	) );

	foreach ($products as $product) {

		$arrProd[ $product->sku ] = [];

		if ($image_id = $product->image_id) {
			$arrImages[] = $image_id;
			$arrProd[ $product->sku ] = [ $image_id ];
		}
		if ($gallery_ids = $product->gallery_image_ids) { 
			$arrImages = array_merge( $arrImages, $gallery_ids );
			$arrProd[ $product->sku ] = array_merge( $arrProd[ $product->sku ], $gallery_ids );
		}

	}



	// Get unattached media (post_parent = 0) -----------------------------------------------------

	$args = array(
		'post_type'      => 'attachment',
		'post_mime_type' => 'image/jpeg,image/gif,image/jpg,image/png',  
		'post_status'    => 'all',  
		'posts_per_page' => -1,
		'post_parent'    => 0,						
		'post__not_in'   => $arrImages,
		'orderby'        => 'post_name',
		'order'          => 'ASC'
	);

	$attachment = new WP_Query($args);


	// Filter the attachment with SKU_index pattern -----------------------------------------------

	$normAtt = [];

	foreach ($attachment->posts as $attachment) {

		$sku_id = wsi_normTitle( $attachment->post_title );

		if ($attachment->post_parent==0) {

			$sku = $sku_id[1];
			$idx = $sku_id[2];

			if (is_numeric($sku) && is_numeric($idx)) {

				$normAtt[ $sku_id[0] ] = [ 
					'id'    => $attachment->ID, 
					'title' => $attachment->post_title, 
					'name'  => $attachment->post_name, 
					'sku'   => $sku,
					'idx'   => $idx
				];

			}

		}

	}


	// Pager --------------------------------------------------------------------------------------

	$big = 999999999; // need an unlikely integer

	$total_pages = ceil( count($normAtt) / $per_page );

	$page_links = paginate_links( array(
	    'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
	    'format' => '?paged=%#%',
	    'current' => max( 1, $paged ),
	   	'total' => $total_pages,
	) );

?>

<div class="wrap">

	<h1>WooCommerce SKU Images : <?php echo __('Free attachments', 'woocommerce-sku-images' ); ?></h1>

	<div class="wsi-callout">
		<p><?php echo __('There may be attachments with SKU_index formated title but not linked to correspondent SKU product. You can perform the following actions:', 'woocommerce-sku-images' ); ?></p>
		<a id="wsi-info" nohref>
			<span class="dashicons dashicons-info"></span>
			<?php echo __('Read more'); ?>...
		<p class="wsi-info">
			<b><?php echo __('Add selected attachments', 'woocommerce-sku-images'); ?></b> : 
			<?php echo __('Add the selected images (first column <i>checkboxes</i>) to the existing gallery product images (even the first one as thumbnail if it does not exist).', 'woocommerce-sku-images' ); ?>
		<p class="wsi-info">
			<b><?php echo __('Replace with selected attachments', 'woocommerce-sku-images'); ?></b> : 
			<?php echo __('Remove the existing product images (thumbnail and gallery) and attach the selected ones (first column <i>checkboxes</i>).', 'woocommerce-sku-images' ); ?>
		</p>
		<p class="wsi-info">
			<b><?php echo __('Remove selected attachments', 'woocommerce-sku-images'); ?></b> : 
			<?php echo __('Remove the selected ones on last column <i>checkboxes</i>.', 'woocommerce-sku-images' ); ?>
		</p>
	</div>


	<hr class="wsi_hr">


	<h2><?php echo count($normAtt) ?> <?php echo __('Free SKU Product Image Attachments', 'woocommerce-sku-images'); ?></h2>

	<form id="wsi-form-free" method="post">

		<input id="wsi-selection" name="wsi-selection" value="" type="hidden" />
		<input id="wsi-action"    name="wsi-action" value="" type="hidden" />

		<div class="tablenav top">

			<div class="alignleft">
				<select id="wsi-option">
					<option value="add"><?php echo __('Add selected attachments', 'woocommerce-sku-images'); ?></option>
					<option value="replace"><?php echo __('Replace with selected attachments', 'woocommerce-sku-images'); ?></option>
					<option value="delete"><?php echo __('Remove selected attachments', 'woocommerce-sku-images'); ?></option>
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
					<th><?php echo __('ID'); ?></th>
					<th>GUID</th>
					<th><?php echo __('Title'); ?> / <?php echo __('Name'); ?></th>
					<th>SKU</th>
					<th><?php echo __('Product attachments', 'woocommerce-sku-images'); ?></th>
					<th><?php echo __('Comment'); ?></th>
					<th><?php echo __('Remove'); ?> <input id="wsi-check-del" type="checkbox" /></th>
				</tr>
			</thead>
			<tbody>
<?php

			foreach ($normAtt as $att) {

				$id    = $att['id'];
				$name  = $att['name'];
				$title = $att['title'];
				$sku   = $att['sku'];
				$idx   = $att['idx'];
				$url   = wp_get_attachment_url( $id );

				$check_att = '';
				$msg       = '';

				if (is_numeric($sku)) {

					if (isset($arrProd[$sku])){

						if (is_numeric($idx)) {

							$check_att = '<input data-id="' . $id . '" data-sku="' . $sku . '" data-index="' . $idx . '"
										           type="checkbox" class="wsi-check-att"/>';

							$arrImg = $arrProd[$sku];
							$img_str = '';

							if (count($arrImg)>0) {

								foreach ($arrImg as $img_id) {
									$thumbnail = wp_get_attachment_image_src( $img_id );
									if ( $thumbnail ) {
   										$img_str .= '<img src="' . $thumbnail[0] . '" width="50" title="' . $img_id . ' : ' . $thumbnail[0] . '" />';
									}
								}
						        	
						  	} else {

						  		$msg = __('Related SKU product without images','woocommerce-sku-images');

						  	}

						} else {

							$msg = __('Not defined index on the image','woocommerce-sku-images');
			
						}

					} else {

						$msg = __('There are no products with this SKU','woocommerce-sku-images');

					}

				} else {

					$sku = $sku . "<br>(not numeric)";
					$msg = __('Undefined SKU','woocommerce-sku-images');

				}

				echo '<tr>
						<td>' . $check_att . '</td>
						<td>' . $id . '</td>
						<td><img src="' . $url . '" width="50" title="' . $url . '"/>
				        <td>' . $title . '<br>' . $name . '</td>
						<td>' . $sku . '</td>
				        <td>' . $img_str . '</td>
					  	<td>' . $msg . '</td>
					  	<td><input data-sku="' . $sku . '" value="' . $id . '" type="checkbox" class="wsi-check-del"/></td>
					  </tr>';

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
        	   selection.push( $(this).val() );
        	});
        	var msg_confirm = "<?php echo __('Do you want to remove the selected images?', 'woocommerce-sku-images'); ?>";
		} else if (action=='add') {
	        $(".wsi-check-att:checked").each(function() {
    	       selection.push( $(this).data('id') + '-' + $(this).data('sku') + '-' + $(this).data('index'));
        	});
        	var msg_confirm = "<?php echo __('Selected images will be added to the product\'s existing ones. \nDo you agree?', 'woocommerce-sku-images'); ?>";
		} else if (action=='replace') {
	        $(".wsi-check-att:checked").each(function() {
    	       selection.push( $(this).data('id') + '-' + $(this).data('sku') + '-' + $(this).data('index'));
        	});
        	var msg_confirm = "<?php echo __('Existing product images will be removed. \nDo you agree?', 'woocommerce-sku-images'); ?>";
		} else {
			alert( "<?php echo __('Select any option', 'woocommerce-sku-images'); ?>" )
		}

		if (action) {
			if (selection.length>0){
				$("#wsi-selection").val( selection.join(',') );
				if (confirm( msg_confirm )) {
					$("#wsi-form-free").submit();
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
