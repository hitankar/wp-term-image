<?php
namespace WPPlugin;

/** 
 * Class to add rich text editor to Author bio/description
 * 
 * @package WPPlugin
 *  @author Hitankar Ray
 * @version $Revision: 1.0.0 $ 
 * @access public 
 */
class TermImage
{
    private static $instance = null;

    /*
    * Initialize the class and start calling our hooks and filters
    * @since 1.0.0
    */
    public function __construct() {

        $args = array(
            'public'   => true,
            '_builtin' => false
        );

        $taxs = array_merge(get_taxonomies($args), array('category' => 'category'));
        
        foreach ($taxs as $tax) {

            add_action( $tax . '_add_form_fields', array ( $this, 'add_taxonomy_image' ), 10, 2 );
            add_action( 'created_' . $tax, array ( $this, 'save_taxonomy_image' ), 10, 2 );
            add_action( $tax . '_edit_form_fields', array ( $this, 'update_taxonomy_image' ), 10, 2 );
            add_action( 'edited_' . $tax, array ( $this, 'updated_taxonomy_image' ), 10, 2 );
            
        }

        add_action( 'admin_enqueue_scripts', array( $this, 'load_media' ) );
        add_action( 'admin_footer', array ( $this, 'add_script' ) );
    }

    // singleton plugin instance mmethod
    public static function getInstance()
    {

        if (self::$instance == null)
        {
        self::$instance = new TermImage();
        }

        return self::$instance;
    }
    
    public function load_media() {
        wp_enqueue_media();
    }
    
    /*
    * Add a form field in the new taxonomy page
    * @since 1.0.0
    */
    public function add_taxonomy_image ( $taxonomy ) { ?>
        <div class="form-field term-group">
        <label for="taxonomy-image-id"><?php _e('Image', 'wpplugin'); ?></label>
        <input type="hidden" id="taxonomy-image-id" name="taxonomy-image-id" class="custom_media_url" value="">
        <div id="taxonomy-image-wrapper"></div>
        <p>
            <input type="button" class="button button-secondary ct_tax_media_button" id="ct_tax_media_button" name="ct_tax_media_button" value="<?php _e( 'Add Image', 'wpplugin' ); ?>" />
            <input type="button" class="button button-secondary ct_tax_media_remove" id="ct_tax_media_remove" name="ct_tax_media_remove" value="<?php _e( 'Remove Image', 'wpplugin' ); ?>" />
        </p>
        </div>
    <?php
    }
    
    /*
    * Save the form field
    * @since 1.0.0
    */
    public function save_taxonomy_image ( $term_id, $tt_id ) {
        if( isset( $_POST['taxonomy-image-id'] ) && '' !== $_POST['taxonomy-image-id'] ){
        $image = $_POST['taxonomy-image-id'];
        add_term_meta( $term_id, 'taxonomy-image-id', $image, true );
        }
    }
    
    /*
    * Edit the form field
    * @since 1.0.0
    */
    public function update_taxonomy_image ( $term, $taxonomy ) { ?>
        <tr class="form-field term-group-wrap">
        <th scope="row">
            <label for="taxonomy-image-id"><?php _e( 'Image', 'wpplugin' ); ?></label>
        </th>
        <td>
            <?php $image_id = get_term_meta ( $term -> term_id, 'taxonomy-image-id', true ); ?>
            <input type="hidden" id="taxonomy-image-id" name="taxonomy-image-id" value="<?php echo $image_id; ?>">
            <div id="taxonomy-image-wrapper">
            <?php if ( $image_id ) { ?>
                <?php echo wp_get_attachment_image ( $image_id, 'thumbnail' ); ?>
            <?php } ?>
            </div>
            <p>
            <input type="button" class="button button-secondary ct_tax_media_button" id="ct_tax_media_button" name="ct_tax_media_button" value="<?php _e( 'Add Image', 'wpplugin' ); ?>" />
            <input type="button" class="button button-secondary ct_tax_media_remove" id="ct_tax_media_remove" name="ct_tax_media_remove" value="<?php _e( 'Remove Image', 'wpplugin' ); ?>" />
            </p>
        </td>
        </tr>
    <?php
    }
    
    /*
    * Update the form field value
    * @since 1.0.0
    */
    public function updated_taxonomy_image ( $term_id, $tt_id ) {
        if( isset( $_POST['taxonomy-image-id'] ) && '' !== $_POST['taxonomy-image-id'] ){
        $image = $_POST['taxonomy-image-id'];
        update_term_meta ( $term_id, 'taxonomy-image-id', $image );
        } else {
        update_term_meta ( $term_id, 'taxonomy-image-id', '' );
        }
    }
    
    /*
    * Add script
    * @since 1.0.0
    */
    public function add_script() { ?>
        <script>
        jQuery(document).ready( function($) {
            function ct_media_upload(button_class) {
            var _custom_media = true,
            _orig_send_attachment = wp.media.editor.send.attachment;
            $('body').on('click', button_class, function(e) {
                var button_id = '#'+$(this).attr('id');
                var send_attachment_bkp = wp.media.editor.send.attachment;
                var button = $(button_id);
                _custom_media = true;
                wp.media.editor.send.attachment = function(props, attachment){
                if ( _custom_media ) {
                    $('#taxonomy-image-id').val(attachment.id);
                    $('#taxonomy-image-wrapper').html('<img class="custom_media_image" src="" style="margin:0;padding:0;max-height:100px;float:none;" />');
                    $('#taxonomy-image-wrapper .custom_media_image').attr('src',attachment.url).css('display','block');
                } else {
                    return _orig_send_attachment.apply( button_id, [props, attachment] );
                }
                }
            wp.media.editor.open(button);
            return false;
            });
        }
        ct_media_upload('.ct_tax_media_button.button'); 
        $('body').on('click','.ct_tax_media_remove',function(){
            $('#taxonomy-image-id').val('');
            $('#taxonomy-image-wrapper').html('<img class="custom_media_image" src="" style="margin:0;padding:0;max-height:100px;float:none;" />');
        });
        // Thanks: http://stackoverflow.com/questions/15281995/wordpress-create-taxonomy-ajax-response
        $(document).ajaxComplete(function(event, xhr, settings) {
            var queryStringArr = settings.data.split('&');
            if( $.inArray('action=add-tag', queryStringArr) !== -1 ){
                var xml = xhr.responseXML;
                $response = $(xml).find('term_id').text();
                if($response!=""){
                    // Clear the thumb image
                    $('#taxonomy-image-wrapper').html('');
                }
            }
        });
        });
    </script>
    <?php }
    
}
