<?php
/*
 * Plugin Name: Popup Lightbox 
 * Plugin URI: http://wordpress.org/extend/plugins/popup-lightbox/ 
 * Requires at least: 3.5.1
 * Tested up to: 3.6.2
 * Stable tag: 1.0
 * Description: Plugin creates a custom post to create popup on your homepage, popups can be configured such date of publication and date of expiration, may be placed video (Youtube), and image only.
 * Version: 1.0
 * Author: Ramon Vicente
 * Author URI: http://umobi.com.br/ 
 * Contributors: Umobi Platform Free 
 * Link: http://wordpress.org/extend/plugins/popup-lightbox/ 
 * Tags: popup, popup lightbox, colorbox, lightbox, banner float, banner, popup scheduler
 * License: GPLv3
 */

if (! defined('PLIGHTBOX_VERSION')) {
    define('PLIGHTBOX_VERSION', '0.1');
}
define('PLIGHTBOX_ABSPATH', dirname(__FILE__));
define('PLIGHTBOX_RELPATH', plugins_url() . '/' . basename(PLIGHTBOX_ABSPATH));

add_action('init', array('PopupLightbox', 'init'));

class PopupLightbox
{

    public static function init ()
    {
    	if (is_admin())
        	self::admin_init_hook();
    	else
    		self::frontend_init_hook();


    	load_plugin_textdomain('popup-lightbox', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public static function admin_init_hook(){
    	
    	self::register_post_type_popup();
    	
    	add_action( 'save_post', array('PopupLightbox', 'save_data') );
    	add_action ('admin_menu', array('PopupLightbox', 'add_submenu_sidebar'));
    	
    	add_filter('manage_edit-popup-lightbox_columns', array('PopupLightbox', 'add_columm_to_list'));
    	
    	add_action('manage_posts_custom_column', array('PopupLightbox', 'manage_posts_custom'));
    }
    
    public static function add_columm_to_list($columns){

        $columns = array(
                'cb' => '<input type="checkbox" />',
                'title' => __('Titulo', "popup-lightbox"),
                'type' => __('Tipo', "popup-lightbox"),
                'status' => __('Status', "popup-lightbox"),
                'date' => __('Data', "popup-lightbox")
            );
        
        return $columns;
    }
    
    public static function manage_posts_custom($column){
        global $post;
        $custom = get_post_custom($post->ID);
        $time = current_time('timestamp');
        switch ($column) {
            case 'status' :
                $status = $custom['popup_agendamento'][0] <= $time && $custom['popup_expirar'][0] >= $time ? 
                	"<span style='color:green'>" . __('Ativada', "popup-lightbox") . "</span>" : 
                	"<span style='color:red'>" . __('Desativada', "popup-lightbox") . "</span>";
                echo "<strong>{$status}</strong>";
            break;
            case 'type' :
                $type = $custom['popup_image'][0] ? __('Image', "popup-lightbox") : __('Video', "popup-lightbox");
                echo "<strong>{$type}</strong>";
            break;
        }
    }
    
    public static function frontend_init_hook(){
        add_action('wp_head', array('PopupLightbox', 'add_popup_script_frontend'), 1);
        add_action('wp_footer', array('PopupLightbox', 'add_popup_frontend'), 100);
    }
    
    public static function add_popup_frontend($a){
        wp_reset_query();
        if(!is_home() && !is_front_page())return;
        
        $time = current_time('timestamp');
        
        $query = array(
        		'post_type' => 'popup-lightbox',
        		'paged' => 1,
        		'posts_per_page' => 1,
        		'meta_query' => array(
        				array(
        						'key' => 'popup_agendamento',
        						'value' => $time,
        						'compare' => '<='
        				),
        				array(
        						'key' => 'popup_expirar',
        						'value' => $time,
        						'compare' => '>='
        				)
        		)
        );
        
        $loop = new WP_Query($query);
       
       if ($loop->have_posts()){
           while ($loop->have_posts()) { $loop->the_post();
           $custom = get_post_custom();
           
           $isToShow = false;
           
           if($custom['popup_one-time'][0] == 1 && $_COOKIE['pl-showed-' . $loop->post->ID]){
				$isToShow = false;
			}else{
				if((int)$custom['popup_freguencia'][0] <= 0){
					$isToShow = true;
				}else{
					if($_COOKIE["pl-open-time-" . $loop->post->ID] < (time() - ((int)$custom['popup_freguencia'][0] * 3600))){
						$isToShow = true;
					}else{
						$isToShow = true;
					}
				}
			}
			
			var_dump($isToShow);
           
           if(($custom['popup_image'][0] || $custom['popup_video'][0]) && $isToShow){
           		$size = @getimagesize($custom['popup_image'][0]);
           		
           		$url = $custom['popup_image_link'][0] ? $custom['popup_image_link'][0] : "javascript:void(0)";
           	?>
            	<script>
            		var POPUP_ID = "<?php echo $loop->post->ID?>";
            	</script>
            	<div id="popup" style="display: none;">
                    <?php if($custom['popup_image'][0]){?>
                    	<a href="<?php echo $url?>">
                        	<img src="<?php echo $custom['popup_image'][0];?>" style="display: block" width="<?php echo $size[0]?>" height="<?php echo $size[1]?>" />
                    	</a>
                    <?php }elseif($custom['popup_video'][0]){?>
                        <iframe style="display: block" width="853" height="480" src="http://www.youtube.com/embed/<?php self::extract_youtube_code();?>?wmode=transparent" frameborder="0" allowfullscreen></iframe>
                    <?php }?>
            	</div>
            <?php
				}
            }
        }
    }
    
    public static function extract_youtube_code($id = 0, $display = true)
    {
        global $post;
        if (! $id)
            $id = $post->ID;
        $c = get_post_custom($id);
        $youtube_url = $c['popup_video'][0];
        if (preg_match("#^.*(youtu.be/|v/|/u/\w/|embed/|watch\?)\??v?=?(.{11}).*#", $youtube_url, $matches))
            if (strlen($matches[2]) == 11)
                if ($display)
                    echo $matches[2];
                else
                    return $matches[2];
     }
    
    public static function add_popup_script_frontend($a){
        if(!is_home() && !is_front_page())return;
        
        wp_enqueue_script('popup-lightbox-colorbox', PLIGHTBOX_RELPATH . '/js/jquery.colorbox-min.js', array(), PLIGHTBOX_VERSION, true);
        wp_enqueue_script('popup-lightbox', PLIGHTBOX_RELPATH . '/js/popup.js', array(), PLIGHTBOX_VERSION, true);
        wp_enqueue_style('popup-lightbox', PLIGHTBOX_RELPATH . '/css/colorbox.css', false, PLIGHTBOX_VERSION);
    }
    
    public static function save_data($post_id){
    	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
    		return;
    	
    	$data = $_POST['pl'] ? $_POST['pl'] : array();
    	$data['one-time'] = $data['one-time'] ? $data['one-time'] : 0;
    	
    	foreach ($data as $k => $v){
    		delete_post_meta($post_id, 'popup_'.$k);
    	}
    	
    	if ($data['type'] == 1) {
    		unset($data['video']);
    	}elseif ($data['type'] == 2) {
    		unset($data['image']);
    	}else{
    		$data = array();
    	}
    	
    	foreach ($data as $k => $v){
    		if (preg_match('#^(\d{2})\/(\d{2})\/(\d{4})\s(\d{2})\:(\d{2})$#', $v, $m))
    			$v = mktime($m[4],$m[5],0,$m[2],$m[1],$m[3]);
    		
    		update_post_meta($post_id, 'popup_'.$k, $v, get_post_meta($post_id, 'popup_'.$k, TRUE));
    	}
    	
    	return $data;
    }

    public static function register_post_type_popup ()
    {
        $labels = array(
        		'name' => __('Popup', "popup-lightbox"), 
        		'singular_name' => __('Popup', "popup-lightbox"), 
        		'add_new' => __('Adicionar Nova', "popup-lightbox"), 
        		'add_new_item' => __('Adicionar Nova Popup', "popup-lightbox"), 
        		'edit_item' => __('Editar Popup', "popup-lightbox"), 
        		'new_item' => __('Nova Popup', "popup-lightbox"), 
        		'all_items' => __('Todas Popups', "popup-lightbox"), 
        		'view_item' => __('Ver Popup', "popup-lightbox"), 
        		'search_items' => __('Procurar Popups', "popup-lightbox"), 
        		'not_found' => __('Nenhuma Popup encontrada', "popup-lightbox"), 
        		'not_found_in_trash' => __('Nenhuma Popup encontrada na lixeira', "popup-lightbox"), 
        		'parent_item_colon' => '', 
        		'menu_name' => __('Popup', "popup-lightbox")
        	);
        
        $args = array(
        		'labels' => $labels, 
        		'public' => false, 
        		'publicly_queryable' => false, 
        		'show_ui' => true, 
        		'show_in_menu' => true, 
        		'capability_type' => 'post', 
        		'has_archive' => true, 
        		'hierarchical' => false, 
        		'menu_position' => 100, 
        		'menu_item' => 50,
        		'menu_icon' => PLIGHTBOX_RELPATH . "/images/calendar.gif",
        		'map_meta_cap' => true, 
        		'supports' => array('title'), 
        		'register_meta_box_cb' => 'PopupLightbox::register_meta_boxes'
        	);
        
        register_post_type('popup-lightbox', $args);
        
    
    }
    
    public static function add_submenu_sidebar(){
        add_submenu_page( 'edit.php?post_type=popup-lightbox', __('Sobre', "popup-lightbox"), __('Sobre', "popup-lightbox"), 'publish_posts', 'popup-lightbox-about', array('PopupLightbox', 'about') ); 
    }

    public static function register_meta_boxes ()
    {
        add_meta_box('popup-data', __('Agendamento', "popup-lightbox"), array('PopupLightbox', 'meta_box_display'), 'popup-lightbox', 'normal');

        add_meta_box('popup-type', __('Tipo de Popup', "popup-lightbox"), array('PopupLightbox', 'meta_box_display_type'), 'popup-lightbox', 'normal');
        add_meta_box('popup-image', __('Imagem da Popup', "popup-lightbox"), array('PopupLightbox', 'meta_box_display_image'), 'popup-lightbox', 'normal');
        add_meta_box('popup-video', __('Video da Popup', "popup-lightbox"), array('PopupLightbox', 'meta_box_display_video'), 'popup-lightbox', 'normal');
    }
    
    public static function meta_box_display_type ()
    {
        global $post;
        $custom = get_post_custom($post->ID);
    	
        $isTypeVideo = @$custom['popup_video'][0] ? true : false;
        
    	echo '<div id="conf-wrapper" class="form-item form-item-textfield">
        <label for="select-popup-type" style="width:120px;display:inline-block;">'.__("Tipo do Popup", "popup-lightbox").'</label>
            <select name="pl[type]" id="select-popup-type">
            	<option value="1" ' . (!$isTypeVideo ? "selected" : "") .'>' . __("Imagem", "popup-lightbox") . '</option>
            	<option value="2" ' . ($isTypeVideo ? "selected" : "") .'>' . __("Vídeo", "popup-lightbox") . '</option>
            </select>
                        		
           	<div style="margin-top: 10px">
            	<label for="pl-one-time"><input type="checkbox" id="pl-one-time" name="pl[one-time]" size="10" value="1" '.($custom['popup_one-time'][0] ? "checked" : "").' />
            	'.__("Caso deseje mostrar o popup uma uníca vez.", "popup-lightbox").'</label>	
            </div>
        	<div id="frequencia-wrapper" style="margin-top: 10px">
    			<label for="pl-freguencia" style="width:120px;display:inline-block;">'.__("Frequência", "popup-lightbox").'</label>
	            <input type="text" id="pl-freguencia" name="pl[freguencia]" size="10" value="' . $custom['popup_freguencia'][0] . '" class="form-textfield textfield" style="width:100px;" />
	    		<div class="description description-textfield">
	            <p>
	    		'.__("Intervalo de horas que o popup aparecerá para o usuário:", "popup-lightbox").'<br />
	    		'.__("0 - para mostrar em todos os carregamentos", "popup-lightbox").'
	        	</p>
    		</div>
        </div>
    	</div>';
    }
    
    public static function meta_box_display_image ()
    {
        global $post;
        $custom = get_post_custom($post->ID);
        
    	echo '<div id="imagem-wrapper" class="form-item form-item-textfield">
        <label for="pl-image-upload-holder" style="width:120px;display:inline-block;">'.__("Imagem do Popup", "popup-lightbox").'</label>
        <input type="text" id="pl-image-upload-holder" name="pl[image]" size="30" value="'.$custom['popup_image'][0].'" class="form-textfield textfield pl-textfield" style="width:60%;" />
        <a href="javascript:void(0);" class="button-primary pl-image-upload trigger-upload" id="pl-image-upload">'.__("Selecionar imagem", "popup-lightbox").'</a>
        <div class="description description-textfield">
            <p>'.__("Arte do Popup", "popup-lightbox").'</p>
        </div>
        <div id="pl-image-upload-holder-preview" class="pl-image-upload-holder-preview">';
    	echo $custom['popup_image'][0] ? '<img src="' . $custom['popup_image'][0] . '" width="100" />' : '';
        echo '</div>
    			
    	<label for="pl-image-link" style="width:120px;display:inline-block;">'.__("Link", "popup-lightbox").'</label>
        <input type="text" id="pl-image-link" name="pl[image_link]" size="30" value="'.$custom['popup_image_link'][0].'" class="form-textfield textfield pl-textfield url" style="width:40%;" />
        
    </div>';
    }
    
    public static function meta_box_display_video ()
    {
        global $post;
        $custom = get_post_custom($post->ID);
        
    	echo '<div id="video-wrapper" class="form-item form-item-textfield">
        <label for="pl-video" style="width:120px;display:inline-block;">'.__("Url Youtube", "popup-lightbox").'</label>
        <input type="text" id="pl-video" name="pl[video]" size="50" value="' . $custom['popup_video'][0] . '" class="form-textfield textfield url" style="width:60%;" />
        <div class="description description-textfield">
            <p>' . __("Link de um video no youtube", "popup-lightbox") . '</p>
        </div>
    </div>';
    }

    public static function meta_box_display ()
    {
        global $post;
        $custom = get_post_custom($post->ID);
        add_thickbox();
        wp_enqueue_media();
        
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script('jquery-ui-slider');
        wp_enqueue_script('wpcf-form-validation', PLIGHTBOX_RELPATH . '/js/jquery.validate.min.js', array('jquery'));
        wp_enqueue_script('wpcf-form-validation-additional', PLIGHTBOX_RELPATH . '/js/additional-methods.min.js', array('jquery'));
        wp_enqueue_script('jquery-ui-timedatepicker', PLIGHTBOX_RELPATH . '/js/jquery-ui-timepicker-addon.js', array('jquery'));
		
        wp_enqueue_style('jquery-ui-datepicker', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.6/themes/smoothness/jquery-ui.css');
        wp_enqueue_style('popup-lightbox', PLIGHTBOX_RELPATH . '/css/style.css');
        
        $data = $custom['popup_expirar'][0];
        
        if($data){
			$custom['popup_agendamento'] = date('d/m/Y H:i', $custom['popup_agendamento'][0]);
			$custom['popup_expirar'] = date('d/m/Y H:i', $custom['popup_expirar'][0]);
		}else{
			$custom['popup_agendamento'] = date('d/m/Y H:i');
			$custom['popup_expirar'] = date('d/m/Y H:i', strtotime("+1 week"));
		}
        
        include_once PLIGHTBOX_ABSPATH . '/includes/meta-box.php';
    }
    
	public static function about(){
		
	}
	
    static function getInstance ()
    {
        if (null === self::$_instance)
            self::$_instance = new self();
        return self::$_instance;
    }
}