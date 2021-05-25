<?php

namespace vihv;

abstract class WpPostType {

    public function getPostType() {
	    return str_replace("\\",'',
			strtolower(
			    get_class()
				)
		    );
    }
    
    function __construct() {
		add_action('init', array($this, 'createPostType'));
        add_action( 'save_post', array( &$this, 'savePost' ), 1, 2);
		if(method_exists($this, 'adminEnqueueScripts')) {
			add_action( 'admin_enqueue_scripts', [$this, 'adminEnqueueScripts'] );
		}
	}

    function saveInputField($post_id, $name) {
        update_post_meta($post_id, $name, $_REQUEST[$name]);
    }
	
	public function saveBoolField($post_id, $name) {
		update_post_meta($post_id, $name, $_REQUEST[$name] == 'on');
	}

    function saveChecklistField($post_id, $name) {
        $Input = $_REQUEST[$name];
//		var_dump($Input); exit;
        if (!is_array($Input)) {
            delete_post_meta($post_id, $name);
            return;
        }
        $ids = array();
        foreach ($Input as $value) {
            $ids[] = (int) $value;
        }
        delete_post_meta($post_id, $name);
        foreach ($ids as $id) {
            add_post_meta($post_id, $name, $id);
        }
    }
    abstract public function createPostType();/* {
		register_post_type(self::getPostType() , array(
		'labels'=>array(
		    'name'=>__('Проекты'),
		    'singular_name'=>__('Проект'),
		    'add_new'=>__('Добавить проект'),
		    'add_new_item'=>__('Добавить проект'),
		    'edit_item'=>__('Редактировать проект'),
		    'view_item'=>__('Просмотреть проект'),
		    'new_item'=>__('Новое проект'),
		),
		'public'=>true,
		'supports'=>array('title','slug','page-attributes', 'thumbnail', 'editor','comments'),
		'show_ui'=>true,
		'description'=>__('Проекты'),
                'register_meta_box_cb' => array($this, 'createMetaboxes'),
		'has_archive'=>true,
	    ));
                 register_taxonomy_for_object_type( 'category', self::POST_TYPE );
                  register_taxonomy_for_object_type( 'post_tag', self::POST_TYPE );
		  add_post_type_support( $this->getPostType(), 'author' );
	}*/

    abstract public function createMetaboxes();/* {
        add_meta_box('rating', "Рейтинг", array($this, 'ratingField'), $this->getPostType(), 'side', 'default');
        add_meta_box('link', 'Ссылка на оригинал', array($this, 'linkField'), $this->getPostType(), 'side', 'default');
    }*/

    public function shouldBeSkipped($post_id) {
	    return  wp_is_post_revision($post_id) 
		    || $_POST['post_type'] != $this->getPostType() ;
    }
    
    abstract public function savePost($post_id, $post);/* {

        if ()
        ) {
            return;
        }
        $this->saveInputField($post_id, 'rating');
        $this->saveInputField($post_id, 'link');
    }*/
	
	public function boolField($post, $metaKey) {
		$val = get_post_meta($post->ID, $metaKey, true);
		$current = (bool)$val;
		?>
		<input type="checkbox" name="<?php echo $metaKey; ?>"
			   <?php
			   if($current) {
				   ?> checked="checked"<?php
			   }
			   ?>
			   />
		<?php
	}
	
	public function stringField($post, $metaKey) {
		$current = get_post_meta($post->ID, $metaKey, true);
		?>
		<input type="text" name="<?php echo $metaKey; ?>"
			   value="<?php echo $current; ?>"/>
		<?php
	}
	
	public function imageField($post, $metaKey) {
		$current = get_post_meta($post->ID, $metaKey, true);
		$imageSrc = wp_get_attachment_image_src( $current, 'middle' );
		$url = '';
		if($imageSrc !== false) {
			$url = $imageSrc[0];
		}
		?>
		<input type="hidden" name="<?php echo $metaKey; ?>" class="meta-image" value="<?php echo $current; ?>"/>
		<img src="<?php echo $url;?>" id="meta-image__thumb__<?php echo $metaKey; ?>" style="max-width:100%"/>
		<input type="button" id="meta-image__button__<?php echo $metaKey; ?>" class="button" value="<?php _e( 'Choose or Upload an Image', TEXT_DOMAIN );?>" />
		<?php
	}
	
	public function imageFieldScript($path, $name) {
		global $typenow;
		if( $typenow == $this->getPostType() ) {
			wp_enqueue_media();

			// Registers and enqueues the required javascript.
			wp_register_script( 'meta-box-image', $path, array( 'jquery' ) );
			wp_localize_script( 'meta-box-image', 'meta_image',
				array(
					'title' => __( 'Choose or Upload an Image', 'prfx-textdomain' ),
					'button' => __( 'Use this image', 'prfx-textdomain' ),
					'name' => $name
				)
			);
			wp_enqueue_script( 'meta-box-image' );
		}
	}
 
    function ratingField($post) {
        $current = get_post_meta($post->ID, 'rating', true);
        for ($i = 0; $i < 5; $i++) {
            ?>
            <?php echo $i + 1; ?><input type="radio" name="rating" value="<?php echo $i; ?>"
            <?php
            if ($i == $current) {
                ?>checked="yes"<?php
                   }
                   ?>
                   />	
                   <?php
               }
           }

           function linkField($post) {
               $current = get_post_meta($post->ID, 'link', true);
               ?>
                http://<input type="text" name="link" value="<?php echo $current; ?>"/>
        <?php
    }

    function beforeSave($data) {
        if (empty($data)) {
            return $data;
        }
        try {
            $this->verify($data);
        } catch (Exception $e) {
            ?>
            <div style="
                 margin: 100px auto;
                 width: 50%;
                 border: 2px solid #00c;
                 color: #00c;
                 padding: 3em;
                 background: #eef;
                 ">
                     <?php echo $e->getMessage(); ?>

                <div style="margin: 1em 0; cursor: pointer;"  onclick="history.go(-1);"><?php echo __('Back');?></div>
            </div>	
            <?php
            exit;
        }
        return $data;
    }

    /**
     * verify post content to be valid html
     * also to be ok with <!-- more ---> tag
     */
    function verify($content) {
        global $wpdb;
        $unescaped = $wpdb->get_var("select '" . $content . "'");
        $exploded = explode("<!--more-->", $unescaped);
        $dom = new DOMDocument();
        foreach ($exploded as $part) {
            if (!@$dom->loadXML("<div>" . $part . "</div>")) {
                throw new Exception(__('Check HTML. HTML contain errors that might ruin page formatting. Saving cancelled.'));
            }
        }
    }

    
    

    function yearField($post) {
        $value = date('Y');
        $current = get_post_meta($post->ID, 'year', true);
        if (!empty($current)) {
            $value = $current;
        }
        ?>
        <input type="number" name="year" min="1900" max="2500" value="<?php echo $value; ?>"/>
        <?php
    }
    
    function authField($post) {
        $current = get_post_meta($post->ID, 'authpub', true);
        ?>
        <input type="text" name="authpub" value="<?php echo $current; ?>"/>
        <?php
    }
    
    function orgField($post) {
        $current = get_post_meta($post->ID, 'orgpub', true);
        ?>
        <input type="text" name="orgpub" value="<?php echo $current; ?>"/>
        <?php
    }

    function fullTitleField($post) {
        $name = 'fulltitle';
        $content = get_post_meta($post->ID, $name, true);
        wp_editor($content, $name . "_id", array('textarea_name' => $name, 'textarea_rows' => 3));
    }

}
