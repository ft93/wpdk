<?php

/**
 * Manage the registered placeholders used to compose a mail from a post, page or any custom post type.
 * See thirth part extensions like Users Manager.
 *
 * @class           WPDKPostPlaceholders
 * @author          =undo= <info@wpxtre.me>
 * @copyright       Copyright (C) 2012-2014 wpXtreme Inc. All Rights Reserved.
 * @date            2014-07-22
 * @version         1.0.1
 * @since           1.5.6
 *
 */
class WPDKPostPlaceholders {

  const DATE              = '${DATE}';
  const DATE_TIME         = '${DATE_TIME}';
  const USER_DISPLAY_NAME = '${USER_DISPLAY_NAME}';
  const USER_EMAIL        = '${USER_EMAIL}';
  const USER_FIRST_NAME   = '${USER_FIRST_NAME}';
  const USER_LAST_NAME    = '${USER_LAST_NAME}';

  /**
   * Return a singleton instance of WPDKPostPlaceholders class
   *
   * @brief Singleton
   *
   * @return WPDKPostPlaceholders
   */
  public static function init()
  {
    static $instance = null;
    if ( is_null( $instance ) ) {
      $instance = new self();
    }

    return $instance;
  }

  /**
   * Create an instance of WPDKPostPlaceholders class
   *
   * @brief Construct
   *
   * @return WPDKPostPlaceholders
   */
  public function __construct()
  {
    // Fires after all built-in meta boxes have been added.
    add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10, 2 );

    // Filter the list of registered placeholder.
    add_filter( 'wpdk_post_placeholders', array( $this, 'wpdk_post_placeholders' ) );

    // Filter the content with standard placeholder
    add_filter( 'wpdk_post_placeholders_content', array( $this, 'wpdk_post_placeholders_content' ), 10, 4 );

    // Filter the standard WPDK (Core) array
    add_filter( 'wpdk_post_placeholders_array', array( $this, 'wpdk_post_placeholders_array' ), 10, 2 );
  }

  /**
   * Return a standard array with user placeholder and values.
   *
   * @since 1.5.8
   *
   * @param array $array   Optional. A custom key value array.
   * @param bool  $user_id Optional. User id or FALSE for current user logged in.
   *
   * @return array
   */
  public function wpdk_post_placeholders_array( $array = array(), $user_id = false )
  {
    // If user id is empty but nobody is logged in exit
    if( empty( $user_id ) && !is_user_logged_in() ) {
      return $array;
    }

    $user = new WPDKUser( $user_id );

    $defaults = array(
      // TODO Think to add a filter for placeholder
      self::DATE              => date( 'j M, Y' ),
      self::DATE_TIME         => date( 'j M, Y H:i:s' ),
      self::USER_DISPLAY_NAME => $user->display_name,
      self::USER_FIRST_NAME   => $user->first_name,
      self::USER_LAST_NAME    => $user->last_name,
      self::USER_EMAIL        => $user->email
    );

    //WPXtreme::log( $defaults );

    return array_merge( $array, $defaults );

  }

  /**
   * Filter the content with standard placeholder.
   *
   * @brief Placeholder content
   *
   * @param string $content       The content.
   * @param int    $user_id       Optional. User id or null for current user.
   * @param array  $replace_pairs Optional. It's an array in the form array( 'from' => 'to', ...).
   * @param array  $args          Optional. Mixed extra params.
   */
  public function wpdk_post_placeholders_content( $content, $user_id = false, $replace_pairs = array(), $args = array() )
  {
    // Merge
    $replaces = apply_filters( 'wpdk_post_placeholders_array', $replace_pairs, $user_id );

    return strtr( $content, $replaces );
  }

  /**
   * Fires after all built-in meta boxes have been added.
   *
   * @since 3.0.0
   *
   * @param string  $post_type Post type.
   * @param WP_Post $post      Post object.
   */
  public function add_meta_boxes( $post_type, $post )
  {
    /**
     * Filter used to display the WPDK Post Placeholders metabox.
     * Usually the placeholders maetabox is display only on post and page post type. If your custom post type would
     * display the placeholders metabox you have add this filter in your register custom post type init.
     *
     * @param bool    $display Set to TRUE to display placeholers metabox. Defaul FALSE.
     * @param WP_Post $post    Post object.
     */
    $display = apply_filters( 'wpdk_post_placeholders_metabox_will_display-' . $post_type, false, $post );

    if ( true === $display || in_array( $post_type, array( 'post', 'page' ) ) ) {

      // Add wpdk post placeholders metabox
      WPDKPostPlaceholdersMetaBoxView::init();

      // Welcome tour in all edit form
      // TODO Check in user post meta for one time view
      add_action( 'edit_form_top', array( WPDKPostPlaceholdersTourModalDialog::init(), 'open' ) );
    }
  }

  /**
   * Filter the list of registered placeholder.
   *
   * @param array $placeholders An array key value pairs with the list of registered placeholders.
   */
  public function wpdk_post_placeholders( $placeholders )
  {

    $wpdk_mail_placeholders = array(
      self::DATE              => array( __( 'Date', WPDK_TEXTDOMAIN ), 'Core' ),
      self::DATE_TIME         => array( __( 'Date & Time', WPDK_TEXTDOMAIN ), 'Core' ),
      self::USER_FIRST_NAME   => array( __( 'User First name', WPDK_TEXTDOMAIN ), 'Core' ),
      self::USER_LAST_NAME    => array( __( 'User Last name', WPDK_TEXTDOMAIN ), 'Core' ),
      self::USER_DISPLAY_NAME => array( __( 'User Display name', WPDK_TEXTDOMAIN ), 'Core' ),
      self::USER_EMAIL        => array( __( 'User email', WPDK_TEXTDOMAIN ), 'Core' ),
    );

    return array_merge( $placeholders, $wpdk_mail_placeholders );
  }

}

/**
 * WPDK Post Placeholders Metabox View
 *
 * @class           WPDKPostPlaceholdersMetaBoxView
 * @author          =undo= <info@wpxtre.me>
 * @copyright       Copyright (C) 2012-2014 wpXtreme Inc. All Rights Reserved.
 * @date            2014-06-04
 * @version         1.0.0
 *
 */
class WPDKPostPlaceholdersMetaBoxView extends WPDKMetaBoxView {
  
  const ID = 'wpdk-post-placeholder-metabox-view';

  /**
   * Return a singleton instance of WPDKPostPlaceholdersMetaBoxView class
   *
   * @brief Singleton
   *
   * @return WPDKPostPlaceholdersMetaBoxView
   */
  public static function init()
  {
    static $instance = null;
    if ( is_null( $instance ) ) {
      $instance = new self();
    }

    return $instance;
  }

  /**
   * Create an instance of WPDKPostPlaceholdersMetaBoxView class
   *
   * @brief Construct
   *
   * @return WPDKPostPlaceholdersMetaBoxView
   */
  public function __construct()
  {
    parent::__construct( self::ID, __( 'Placeholders' ), null, WPDKMetaBoxContext::SIDE, WPDKMetaBoxPriority::HIGH );
  }

  /**
   * Display the HTML markup content for this view.
   *
   * @brief Display view content
   *
   * @return string
   */
  public function display()
  {
    /**
     * Filter the list of registered placeholder.
     *
     * @param array $placeholders An array key value pairs with the list of registered placeholders.
     */
    $placeholders = apply_filters( 'wpdk_post_placeholders', array() );

    // Reverse :)
    $placeholders = array_reverse( $placeholders, true );

    // This is impossible
    if( empty( $placeholders ) ) {
      _e( 'No PLaceholders registered/found' );
    }

    // Owner array
    $owners = array();

    // Build the owner select combo
    foreach ( $placeholders as $placeholder_key => $info ) {
      $key           = sanitize_title( $info[1] );
      $owners[ $key ] = $info[1];
    }
    ?>

    <select id="wpdk-post-placeholder-select" class="wpdk-ui-control wpdk-form-select">
      <option selected="selected" style="display:none" disabled="disabled"><?php _e( 'Filter by Owner' ) ?></option>
      <option value=""><?php _e( 'All' ) ?></option>
      <?php foreach( $owners as $key => $owner ) : ?>
        <option value="<?php echo $key ?>"><?php echo $owner ?></option>
      <?php endforeach ?>
    </select>

    <div class="wpdk-post-placeholders"><?php

    // Group by owner
    $owner = '';

    // Loop into the placeholders
    foreach( $placeholders as $placeholder_key => $info ) : ?>

      <?php echo ( $owner != $info[1] ) ? sprintf( '<small>%s</small>', $info[1] ) : '' ?>
      <?php $owner = $info[1] ?>

      <a onclick="window.parent.send_to_editor('<?php echo $placeholder_key ?>')"
            data-owner="<?php echo sanitize_title( $info[1] ) ?>"
            title="<?php echo $placeholder_key ?>"
            href="#"><?php printf( '%s %s', WPDKGlyphIcons::html( WPDKGlyphIcons::ANGLE_LEFT ), $info[0] ) ?></a>

    <?php endforeach; ?>

    </div>

    <script type="text/javascript">
      (function ( $ )
      {
        // Select
        var $select = $( '#wpdk-post-placeholder-select' );

        // Display by owner
        $select.on( 'change', function ()
        {
          if( empty( $( this ).val() ) ) {
            $( '.wpdk-post-placeholders' ).find( 'a,small' ).show();
          }
          else {
            $( '.wpdk-post-placeholders' ).find( 'a,small' ).hide();
            $( '.wpdk-post-placeholders' ).find( 'a[data-owner="'+ $( this ).val() +'"]' ).show();
          }
        } );

      })( jQuery );
    </script>

  <?php
  }

}

/**
 * WPDK Post Placeholder tour.
 *
 * @class           WPDKPostPlaceholdersTourModalDialog
 * @author          =undo= <info@wpxtre.me>
 * @copyright       Copyright (C) 2012-2014 wpXtreme Inc. All Rights Reserved.
 * @date            2014-06-05
 * @version         1.0.0
 *
 */
class WPDKPostPlaceholdersTourModalDialog extends WPDKUIModalDialog {

  /**
   * An instance of WPDKUIPageView class
   *
   * @brief Page view
   *
   * @var WPDKUIPageView $page_view
   */
  private $page_view;

  /**
   * Return a singleton instance of WPDKPostPlaceholdersTourModalDialog class
   *
   * @brief Singleton
   *
   * @return WPDKPostPlaceholdersTourModalDialog
   */
  public static function init()
  {
    static $instance = null;
    if ( is_null( $instance ) ) {
      $instance = new self();
    }

    return $instance;
  }

  /**
   * Create an instance of WPDKPostPlaceholdersTourModalDialog class
   *
   * @brief Construct
   *
   * @return WPDKPostPlaceholdersTourModalDialog
   */
  public function __construct()
  {
    parent::__construct( 'wpdk-post-placeholder-welcome-tour', __( 'New Placeholders Metabox' ) );

    // Check if dismissed
    if( false === $this->is_dismissed() ) {

      // Permanent dismiss
      $this->permanent_dismiss = true;

      // Enqueue page view
      WPDKUIComponents::init()->enqueue( WPDKUIComponents::PAGE );

      // List of page
      $pages = array(
        'Prima',
        'Seconda',
      );

      // Display the page view
      $this->page_view = WPDKUIPageView::initWithPages( $pages );
    }
  }

  /**
   * Content
   *
   * @brief Content
   * @return string
   */
  public function content()
  {
    return $this->page_view->html();
  }

  /**
   * Footer
   *
   * @brief Footer
   * @return string
   */
  public function footer()
  {
    return $this->page_view->navigator();
  }

}