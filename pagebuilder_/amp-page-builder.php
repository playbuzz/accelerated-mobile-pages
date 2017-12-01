<?php
if ( ! defined( 'ABSPATH' ) ) {
	echo "Silence is golden"; 
}

// Identifies the current plugin version.
define( 'AMP_PAGE_BUILDER', plugin_dir_path(__FILE__) );
define( 'AMP_PAGE_BUILDER_URL', plugin_dir_url(__FILE__) );

//Set Metabox
add_action('add_meta_boxes','ampforwp_pagebuilder_content_meta_register', 10 ,1);
function ampforwp_pagebuilder_content_meta_register($post_type){
	global $redux_builder_amp;

    $user_level = '';
    $user_level = current_user_can( 'manage_options' );

    if (  isset( $redux_builder_amp['amp-meta-permissions'] ) && $redux_builder_amp['amp-meta-permissions'] == 'all' ) {
    	$user_level = true;
    }

    if ( $user_level ) {
		// Page builder for posts
	  	if( $redux_builder_amp['amp-on-off-for-all-posts'] && $post_type == 'post' ) {
	  		add_meta_box( 'pagebilder_content', __( 'AMP Page Builder', 'amp-page-builder' ), 'amp_content_pagebuilder_title_callback',  'post' , 'normal', 'default' );
	  	}
	  	// Page builder for pages
	  	if ( $redux_builder_amp['amp-on-off-for-all-pages'] && $post_type == 'page' ) {
	  		add_meta_box( 'pagebilder_content', __( 'AMP Page Builder', 'amp-page-builder' ), 'amp_content_pagebuilder_title_callback',  'page' , 'normal', 'default' );
	  	}
	}
}

function amp_content_pagebuilder_title_callback( $post ){
	global $post;
	$amp_current_post_id = $post->ID;
	$content 		= get_post_meta ( $amp_current_post_id, 'ampforwp_custom_content_editor', true );
	$editor_id 	= 'ampforwp_custom_content_editor';
	//wp_editor( $content, $editor_id );
	
	//echo "<textarea style='display:none' id='amp-content-preview'>$content</textarea>";
	/*echo "<div class='rander_amp_html'>";
		echo html_entity_decode($content);	
	echo "</div>";*/
	


	//previous data stored compatible
	//echo get_post_meta( $amp_current_post_id, 'amp-page-builder', true );
	if(get_post_meta($amp_current_post_id ,'use_ampforwp_page_builder',true)==null && 
		get_post_meta( $amp_current_post_id, 'amp-page-builder', true ) != ''){
		update_post_meta($amp_current_post_id, 'use_ampforwp_page_builder','yes');
	}
	//Disable page builder
	if(isset($_GET['ramppb']) && $_GET['ramppb']=='1'){
		delete_post_meta($amp_current_post_id, 'use_ampforwp_page_builder','yes');
		delete_post_meta($amp_current_post_id, 'ampforwp_page_builder_enable','yes');
	}
	//Enable page builder
	if(isset($_GET['use_amp_pagebuilder']) && $_GET['use_amp_pagebuilder']=='1'){
		update_post_meta($amp_current_post_id, 'use_ampforwp_page_builder','yes');
	}
	if(get_post_meta($amp_current_post_id ,'use_ampforwp_page_builder',true)=='yes'){
		$url = remove_query_arg('use_amp_pagebuilder');

		if(empty($content)){
			echo "<div class='amppb_welcome'>
	                    <a class='amppb_helper_btn beta_btn' href='https://ampforwp.com/tutorials/article/page-builder-is-in-beta/' target='_blank'><span>Beta Feature</span></a>
	                    <a class='amppb_helper_btn video_btn' href='https://ampforwp.com/tutorials/article/amp-page-builder-installation/' target='_blank'><span>Video Tutorial</span></a>

	                    <a class='amppb_helper_btn leave_review' href='https://wordpress.org/support/view/plugin-reviews/accelerated-mobile-pages?rate=5#new-post' target='_blank'><span>Rate</span></a>
				</div>";
		}

		wp_enqueue_script( 'jquery-ui-dialog' ); // jquery and jquery-ui should be dependencies, didn't check though...
		wp_enqueue_style( 'wp-jquery-ui-dialog' );

		// echo "<div class='amppb_welcome'>
		// 	 <a class='amppb_helper_btn' href='".add_query_arg('ramppb','1',$url)."' style='margin-right:285px;'><span>Remove</span></a>
		// </div>";

		call_page_builder();
	}else{
		$url = remove_query_arg('ramppb');
		echo '<div href="'.add_query_arg('use_amp_pagebuilder','1',$url).'" id="start_amp_pb_post" class="start_amp_pb" data-postId="'.get_the_ID().'" onclick="">Start the AMP Page Builder</div>';
	}
}

add_action("wp_ajax_call_page_builder", "call_page_builder");

/* Add page builder form after editor */
function call_page_builder(){
	global $post;
	global $moduleTemplate;
	if($post!=null){
		$postId = $post->ID;
	}
	if(isset($_GET['post_id'])){
		$postId = $_GET['post_id'];
	}
	add_thickbox();
	if(isset($postId) && get_post_meta($postId,'use_ampforwp_page_builder')!='yes'){
		update_post_meta($postId, 'use_ampforwp_page_builder','yes');
	}

	$previousData = get_post_meta($postId,'amp-page-builder');
	$ampforwp_pagebuilder_enable = get_post_meta($postId,'ampforwp_page_builder_enable', true);
	$previousData = isset($previousData[0])? $previousData[0]: null;
	
	$previousData = (str_replace("'", "", $previousData));
	
	$totalRows = 1;
	$totalmodules = 1;
	if(!empty($previousData)){
		//echo ' sdcds '.json_encode($previousData);die;
		$jsonData = json_decode($previousData,true);
		if(count($jsonData['rows'])>0){
			$totalRows = $jsonData['totalrows'];
			$totalmodules = $jsonData['totalmodules'];
			$previousData = json_encode($jsonData);
		}else{
			$jsonData['rows'] = array();
			$jsonData['totalrows']=1;
			$jsonData['totalmodules'] = 1;
			$previousData = json_encode($jsonData);
		}
	}
	
	require_once(AMP_PAGE_BUILDER."config/moduleTemplate.php");
	wp_nonce_field( basename( __FILE__) , 'amp_content_editor_nonce' );
	?>
	<div id="ampForWpPageBuilder_container">
		{{message}}
		<div class="enable_ampforwp_page_builder">
			<label><input type="checkbox" name="ampforwp_page_builder_enable" value="yes" <?php if($ampforwp_pagebuilder_enable=='yes'){echo 'checked'; } ?> >Enable Builder</label>
			<label  @click="showModal = true">settings</label>
		</div>
		<div id="amp-page-builder">
	 		<?php wp_nonce_field( "amppb_nonce_action", "amppb_nonce" ) ?>
	        <input type="hidden" name="amp-page-builder" id="amp-page-builder-data" class="amp-data" v-model="mainContent_Save" value='<?php echo $previousData; ?>'>
	        <?php /* This is where we gonna add & manage rows */ ?>
			<div id="sorted_rows" class="amppb-rows drop">
				<draggable v-model="mainContent.rows" :options="{draggable:'.row'}">
					<div v-for="(row, key, index) in mainContent.rows" :key="row.id" class="amppb-row " :class="'amppb-col-'+row.id">
							<div class="row" v-if="row.cells==1" :id="'conatiner-'+row.id">
						 		<input type="hidden" name="column-data" value="">
						        <div class="amppb-row-title">
						            <span class="amppb-handle dashicons dashicons-move"></span>
						            <span class="amppb-row-title-text">1 Column</span>
						            <span @click="reomve_row(key)" class="amppb-remove dashicons dashicons-trash"></span>
						            <a href="#" @click="showModulePopUp($event)" class="rowBoxContainer" title="Row settings column 1" :data-popupcontent='<?php echo json_encode($containerCommonSettings); ?>'>
						            	<span class="tools-icon dashicons dashicons-menu"></span>
						            </a>
						        </div><!-- .amppb-row-title -->
						 
						        <div class="amppb-row-fields col" data-cell="1">
						        	<div class="modules-drop" :class="{'ui-droppable': row.cell_data.length==0 }">
						        		
											<module-data
											 v-for="(cell, key, index)  in row.cell_data" 
											 :key="cell.cell_id"
											 :cell="cell" 
											 :cellcontainer="1"
					        				></module-data>
						        	</div>
						        </div><!-- .amppb-row-fields -->
						    </div><!-- .amppb-row.amppb-col-1 -->

						    <div v-if="row.cells==2" class="row amppb-col-2" :id="'conatiner-'+row.id">
						 		<input type="hidden" name="column-data" value="">
						        <div class="amppb-row-title">
						            <span class="amppb-handle dashicons dashicons-move"></span>
						            <span class="amppb-row-title-text">2 Columns</span> 
						            <span @click="reomve_row(key)" class="amppb-remove amppb-item-remove dashicons dashicons-trash"></span>
						            <a href="#" @click="showModulePopUp($event)" class="rowBoxContainer" title="Row settings column 2" :data-popupContent='<?php echo json_encode($containerCommonSettings); ?>'>
						            	<span class="tools-icon dashicons dashicons-menu"></span>
						            </a>
						        </div><!-- .amppb-row-title -->
						 
						        <div class="amppb-row-fields ">
					        	    <div class="amppb-column-2-left col" data-cell="1">
				        	    		<div class="modules-drop" :class="{'ui-droppable': row.cell_left.length==0 }">
						            		<module-data
												 v-for="(cell, key, index)  in row.cell_data" :key="cell.cell_id" 
												 :key="cell.cell_id"
												 :cell="cell" 
												 :cellcontainer="1"
						        				></module-data>
						            	</div>
						            </div><!-- .amppb-col-2-left -->
						            <div class="amppb-column-2-right col" data-cell="2">
						            	<div class="resize-handle"></div>
						            		<div class="modules-drop" :class="{'ui-droppable': row.cell_right.length==0 }">
											
													<module-data
													 v-for="(cell, key, index)  in row.cell_data" :key="cell.cell_id" 
													 :key="cell.cell_id"
													 :cell="cell" 
													 :cellcontainer="2"
							        				></module-data>
													
											
											</div>
						            </div><!-- .amppb-col-2-right -->
						        </div><!-- .amppb-row-fields -->
						    </div><!-- .amppb-row.amppb-col-2 -->
			          	</div>
				    </draggable>
				
		</div><!-- .amppb-rows -->

	
         	<div class="amppb-actions" id="amppb-actions-container" data-containerid="<?php echo $totalRows; ?>">
	        	<div class="drag" :transfer-data="{type: 'column',value: 'col-1'}" >
				    <span id="action-col-1" class="amppb-add-row button-primary button-large module-col-1" data-template="col-1"
				    >1 Column</span>
				</div>
				<div class="drag" :transfer-data="{type: 'column',value: 'col-2'}">
				    <span id="action-col-2" class="amppb-add-row button-primary button-large draggable module-col-2" data-template="col-2"
				    >2 Columns</span>
				</div>
	       		<div class="clearfix"></div>
	        </div><!-- .amppb-actions -->
	        <div class="amppb-module-actions" id="amppb-module-actions-container" data-recentid="<?php echo $totalmodules; ?>">
			    <?php
			    foreach ($moduleTemplate as $key => $module) {
			    	$moduleJson = array('type'=> 'module','modulename'=>strtolower($module['name']),'moduleJson'=>$module);
			    	echo '
			    	<div class="drag" :transfer-data=\''.json_encode($moduleJson).'\' :draggable="true">
				    	<span class="amppb-add-row button-primary button-large draggable module-'.strtolower($module['name']).'"
				    	>
				    		'.$module['label'].'
				    	</span>
			    	</div>
			    	';
			    }
			    ?>
			    <div class="clearfix"></div>
			</div><!-- .amppb-module-actions -->
















		        
	        
	        <?php /* This is where our action buttons to add rows 
				Modules
	        */ ?>

			<!-- use the modal component, pass in the prop -->
			<amp-pagebuilder-modal v-if="showModal" @close="showModal = false">
				<!--
				  you can use custom content here to overwrite
				  default content
				-->
				<h3 slot="header">custom header</h3>
			</amp-pagebuilder-modal>
			<amp-pagebuilder-module-modal v-if="showmoduleModal" @close="showmoduleModal = false">
				<!--
				  you can use custom content here to overwrite
				  default content
				-->
				
			</amp-pagebuilder-module-modal>
			

	        
	        
	    </div>
	    
	
	</div>
    <?php
    if(isset($_GET['post_id'])){
		exit;
	}
}

// Ajax action to refresh the user image
add_action( 'wp_ajax_ampforwp_get_image', 'ampforwp_get_image');
function ampforwp_get_image() {
    if(isset($_GET['id']) ){
		if(strpos($_GET['id'],",") !== false){
			$get_ids = explode(",", $_GET['id']);
			
			if(count($get_ids)>0){
				foreach($get_ids as $id){
					$image = wp_get_attachment_image( $id, 'medium', false, array( 'id' => 'ampforwp-preview-image' ) );
					$image_src = wp_get_attachment_image_src($id, 'medium', false);
					$data[] = array(
						'image'    => $image,
						'detail'	   => $image_src
					);

				}
			}
		}else{
			$image = wp_get_attachment_image( filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT ), 'medium', false, array( 'id' => 'ampforwp-preview-image' ) );
			$data = array(
				'image'    => $image,
			);
		}
        wp_send_json_success( $data );
    } else {
        wp_send_json_error();
    }
}

require_once AMP_PAGE_BUILDER.'functions.php';