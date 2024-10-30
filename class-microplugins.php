<?php
/**
 * @package Microplugins
 * @version 1.1.0
 */

/**
 * Singleton que contiene la funcionalidad del plugin.
 *
 * @author Andy D. Navarro Taño <andaniel05@gmail.com>
 */
class Microplugins
{
    const VERSION   = '1.1.0';
    const POST_TYPE = 'microplugin';

    /**
     * Instancia única de la clase
     *
     * @var Object
     */
    protected static $instance;

    /**
     * Contiene los mensajes que se mostrarán en la administración.
     *
     * @var array
     */
    protected static $admin_messages;

    /**
     * Constructor.
     */
    protected function __construct()
    {
        if (true === self::check_file_permissions()) {

            register_shutdown_function(array(__CLASS__, 'shutdown_function'));
            set_error_handler(array(__CLASS__, 'error_handler'));

            self::run_microplugins();

            add_action('init', array(__CLASS__, 'register_post_type'));
            add_action('init', array(__CLASS__, 'register_taxonomies'));

            if (true === is_admin()) {
                add_action('add_meta_boxes', array(__CLASS__, 'add_editor_metabox'));
                add_action('admin_menu', array(__CLASS__, 'add_recompile_all_submenu'));
                add_action('save_post', array(__CLASS__, 'save_post_action'));
            }

            self::$admin_messages = array();

        } else {
            $message = sprintf( __('Microplugins needs read and write permissions on the "%s" directory. Actually file permissions are %d.', 'microplugins'), MICROPLUGINS_CACHE_DIR, fileperms(MICROPLUGINS_CACHE_DIR) );
            self::add_admin_notice($message, 'warning');
        }
    }

    /**
     * Devuelve la instancia de la clase.
     *
     * @return Object
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            $instance = new Microplugins;
        }

        return self::$instance;
    }

    /**
     * Ejecuta los microplugins.
     *
     * @return null
     */
    public static function run_microplugins()
    {
        // Desactiva el microplugin que produjo un error fatal en la
        // petición anterior.
        //

        $errorFilename = MICROPLUGINS_CACHE_DIR . '/error';
        if (true === file_exists($errorFilename)) {

            $error = json_decode(file_get_contents($errorFilename), true);
            if (true === self::is_microplugin_error($error)) {

                unlink($errorFilename);
                unlink($error['file']);

                $func = function() use ($error) {
                    Microplugins::process_mircoplugin_error($error);
                };

                add_action('init', $func);
            }
        }

        // Ejecuta los microplugins
        //

        $last_error_reporting = error_reporting(0);

        // Procesa cada archivo de código.
        $files = glob(MICROPLUGINS_CACHE_DIR . '/*.php');
        foreach ($files as $filename) {
            include_once $filename;
        }

        error_reporting($last_error_reporting);
    }

    /**
     * Genera un archivo de código PHP a partir del contenido de una entrada
     * de tipo microplugin.
     *
     * Los archivos son guardados en la carpeta 'cache'.
     *
     * @param  integer $post_id Id de la entrada
     * @return boolean
     */
    public static function dump($post_id)
    {
        $post = get_post($post_id);

        if (true === $post instanceOf WP_Post && $post->post_type == self::POST_TYPE && $post->post_status == 'publish') {
            return file_put_contents(MICROPLUGINS_CACHE_DIR . "/{$post->ID}.php", $post->post_content);
        }

        return false;
    }

    /**
     * Borra el archivo de código de un microplugin.
     *
     * @param  integer $post_id Id de la entrada.
     * @return boolean
     */
    public static function clear($post_id)
    {
        $post = get_post($post_id);

        if (true === $post instanceOf WP_Post && $post->post_type == self::POST_TYPE) {
            $filename = MICROPLUGINS_CACHE_DIR . "/{$post_id}.php";
            if (true === file_exists($filename)) {
                return unlink($filename);
            }
        }

        return false;
    }

    /**
     * Registra el tipo de entrada 'microplugin'
     *
     * @return null
     */
    public static function register_post_type()
    {
        $labels = array(
            'name'               => __('Microplugins', 'microplugins'),
            'singular_name'      => __('Microplugin', 'microplugins'),
            'add_new'            => __('Add New', 'microplugins'),
            'add_new_item'       => __('Add New Microplugin', 'microplugins'),
            'edit_item'          => __('Edit Microplugin', 'microplugins'),
            'new_item'           => __('New Microplugin', 'microplugins'),
            'all_items'          => __('All Microplugins', 'microplugins'),
            'view_item'          => __('View Microplugin', 'microplugins'),
            'search_items'       => __('Search Microplugins', 'microplugins'),
            'not_found'          => __('No microplugins found', 'microplugins'),
            'not_found_in_trash' => __('No microplugins found in Trash', 'microplugins'),
            'parent_item_colon'  => '',
            'menu_name'          => __('Microplugins', 'microplugins')
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array( 'slug' => self::POST_TYPE ),
            'capability_type'    => array( 'microplugin', 'microplugins' ),
            'map_meta_cap'       => true,
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title', 'revisions')
        );

        register_post_type( self::POST_TYPE, $args );
    }

    /**
     * Añade al menú la página 'Recompile All'.
     *
     * @return null
     */
    public static function add_recompile_all_submenu()
    {
        add_submenu_page(
            'edit.php?post_type=' . self::POST_TYPE,
            __('Recompile All Microplugins', 'microplugins'),
            __('Recompile All', 'microplugins'),
            'manage_options',
            'recompile-all',
            array(__CLASS__, 'recompile_all_page')
            )
        ;
    }

    /**
     * Muestra el contenido de la página 'Recompile All'.
     *
     * @return null
     */
    public static function recompile_all_page()
    {
        ?>
        <div class="wrap">
            <h3><?php _e('Deleting old files', 'microplugins') ?></h2>
            <?php
                $files = glob(MICROPLUGINS_CACHE_DIR . '/*');
                if (empty($files)) {
                    echo '<p>' . __('No files to delete.', 'microplugins') . '</p>';
                }
                foreach ($files as $filename) {
                    $str = sprintf( __('Deleting %s...', 'microplugins'), basename($filename) );
                    $str .= unlink($filename) ? __('OK', 'microplugins') : __('ERROR', 'microplugins');
                    echo "<p>$str</p>";
                }
            ?>
            <br>
            <h3><?php _e('Generating new microplugins', 'microplugins') ?></h2>
            <?php
                $posts = get_posts( array('post_type' => self::POST_TYPE, 'posts_per_page' => -1) );
                if (empty($posts)) {
                    echo '<p>' . __('No microplugins to generate.', 'microplugins') . '</p>';
                }
                foreach ($posts as $post) {
                    $str = sprintf( __('Generating microplugin %d...', 'microplugins'), intval($post->ID) );
                    $str .= self::dump($post->ID) ? __('OK', 'microplugins') : __('ERROR', 'microplugins');
                    echo "<p>$str</p>";
                }
            ?>
        </div>
        <?php
    }

    /**
     * Inserta el editor de código.
     *
     * @return null
     */
    public static function add_editor_metabox()
    {
        wp_register_style( 'microplugin-editor', MICROPLUGINS_URI . 'assets/editor.css', array(), '' );
        wp_enqueue_style('microplugin-editor');

        wp_enqueue_script( 'ace', MICROPLUGINS_URI . 'assets/vendor/ace/ace.js', array(), '', true );
        wp_enqueue_script( 'ace-mode-php', MICROPLUGINS_URI . 'assets/vendor/ace/mode-php.js', array('ace'), '', true );

        // Agrega los estilos de los temas.
        foreach (self::get_ace_themes() as $style => $styleThemes) {
            foreach ($styleThemes as $themeDef) {
                wp_enqueue_script( "ace-theme-{$themeDef['id']}", MICROPLUGINS_URI . "assets/vendor/ace/theme-{$themeDef['id']}.js", array('ace'), '', true );
            }
        }

        wp_enqueue_script( 'microplugin-editor', MICROPLUGINS_URI . 'assets/microplugin-editor.js', array('ace'), '', true );

        add_meta_box(
            'microplugin-editor',
            __('Source Code Editor', 'microplugins'),
            array(__CLASS__, 'render_editor_metabox'),
            self::POST_TYPE,
            'normal',
            'low',
            array()
            )
        ;
    }

    /**
     * Muestra el contenido del editor de código.
     *
     * @return null
     */
    public static function render_editor_metabox()
    {
        global $post;

        $comment = __('Write here the microplugin description.', 'microplugins');
        $post_content = <<<EOT
<?php
/**
 * $comment
 */


EOT;
        if (true === $post instanceOf WP_Post && false === empty($post->post_content)) {
            $post_content = $post->post_content;
        }

        $microplugin_php_errors = get_post_meta( $post->ID, 'microplugin_php_errors', true );
        if (false === is_array($microplugin_php_errors)) {
            $microplugin_php_errors = array();
        }

        ?>
        <div class="editor-toolbar" style="padding: 5px 10px; box-sizing: border-box">

            <?php _e('Theme', 'microplugins') ?>:
            <?php $themes = self::get_ace_themes() ?>
            <select id="micropluginEditorThemeSelect">
                <optgroup label="Bright">
                    <?php foreach ($themes['bright'] as $value) : ?>
                        <option value="ace/theme/<?php echo $value['id'] ?>"><?php echo $value['name'] ?></option>
                    <?php endforeach ?>
                </optgroup>
                <optgroup label="Dark">
                    <?php foreach ($themes['dark'] as $value) : ?>
                        <option value="ace/theme/<?php echo $value['id'] ?>"><?php echo $value['name'] ?></option>
                    <?php endforeach ?>
                </optgroup>
            </select>

            <?php _e('Font Size', 'microplugins') ?>:
            <select id="micropluginEditorFontSize" size="1">
                 <option value="10">10px</option>
                 <option value="12">12px</option>
                 <option value="14">14px</option>
                 <option value="16">16px</option>
                 <option value="18" selected="selected">18px</option>
                 <option value="20">20px</option>
                 <option value="22">22px</option>
                 <option value="24">24px</option>
                 <option value="26">26px</option>
                 <option value="28">28px</option>
                 <option value="30">30px</option>
            </select>

        </div>
        <div class="editing-area">
            <div id="micropluginEditor"></div>
            <textarea name="content" id="postContent" cols="30" rows="10" style="display:none"><?php echo $post_content ?></textarea>
            <?php foreach ($microplugin_php_errors as $php_error) : ?>
                <div class="php-error" data-type="<?php echo $php_error['type'] ?>" data-message="<?php echo $php_error['message'] ?>" data-file="<?php echo $php_error['file'] ?>" data-line="<?php echo $php_error['line'] ?>"></div>
            <?php endforeach ?>
        </div>
        <!-- <div class="editor-footer"></div> -->
        <?php
    }

    /**
     * Vuelca un microplugin al guardar o actualizar la entrada.
     *
     * @param  int $post_id
     * @param  WP_Post $post
     * @return null
     */
    public static function save_post_action($post_id)
    {
        $post = get_post($post_id);

        if (true === $post instanceOf WP_Post && $post->post_type == self::POST_TYPE) {

            if ($post->post_status == 'publish') {
                self::dump($post_id);
                delete_post_meta( $post_id, 'microplugin_php_errors' );
            } else {
                self::clear($post_id);
            }

        }
    }

    /**
     * Procesa los errores fatales generados por los microplugins al terminar
     * la ejecución del script.
     *
     * @return null
     */
    public static function shutdown_function()
    {
        $last_error = error_get_last();
        if (true === self::is_microplugin_error($last_error)) {

            $errorFilename = MICROPLUGINS_CACHE_DIR . '/error';
            file_put_contents($errorFilename, json_encode($last_error));

            ?>
            <script type="text/javascript">location.reload()</script>
            <?php
        }
    }

    /**
     * Determina si un array de error es un error producido por un microplugin.
     *
     * @param  array   $error
     * @return boolean
     */
    protected static function is_microplugin_error($error)
    {
        $result = false;

        if (true === is_array($error)) {

            $isError = (bool) isset($error['type']) && isset($error['message'])
                && isset($error['file']) && isset($error['line']);

            if (true === $isError && false != strpos($error['file'], 'microplugins')
                && false != strpos($error['file'], 'cache'))
            {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Muestra mensajes de notificación en la administración de WordPress.
     *
     * @param string  $message       Contenido del mensaje
     * @param string  $type          Sus posibles valores son "error", "warning", "success", "info"
     * @param boolean $isDismissible Indica si el mensaje se puede cerrar.
     */
    public static function add_admin_notice($message, $type, $isDismissible = true)
    {
        $key = $message . $type;
        if (false === in_array($key, self::$admin_messages)) {

            $class = "notice notice-$type";
            if (true === $isDismissible) $class .= ' is-dismissible';

            $func = function() use ($message, $class) {
                ?>
                <div class="<?php echo $class ?>">
                    <p><?php echo $message ?></p>
                </div>
                <?php
            };

            add_action('admin_notices', $func);
            self::$admin_messages[] = $key;
        }
    }

    /**
     * Procesa un error producido por un microplugin.
     *
     * @param  array $error
     * @return null
     */
    public static function process_mircoplugin_error($error)
    {
        if (true === self::is_microplugin_error($error)) {

            $fatal_errors = array(
                1,   // E_ERROR
                4,   // E_PARSE
                16,  // E_CORE_ERROR
                32,  // E_CORE_WARNING
                64,  // E_COMPILE_ERROR
                128, // E_COMPILE_WARNING
                )
            ;

            $is_fatal = (true === in_array($error['type'], $fatal_errors)) ? true : false;

            $post_id = intval(basename($error['file'], '.php'));
            $post    = get_post($post_id);
            $args    = array( 'ID' => $post_id, 'post_status' => 'pending' );

            // Si el error es fatal establece la entrada como pendiente.
            if (true === $is_fatal) {
                self::clear($post_id);
                wp_update_post($args);
            }

            // Guarda la información del error en la entrada.
            //

            $post_errors = get_post_meta($post_id, 'microplugin_php_errors', true);
            if (false === is_array($post_errors)) {
                $post_errors = array();
            }

            $post_errors[] = $error;

            update_post_meta($post_id, 'microplugin_php_errors', $post_errors);

            // Muestra un mensaje en la administración.
            //

            $admin_url = admin_url();
            $edit_post_link = $admin_url . "post.php?post=$post_id&action=edit";

            $message = '';
            $type    = '';

            if (true === $is_fatal) {
                $type = 'error';
                $message = sprintf( __('The %d microplugin with title "%s" has been disabled for have fatal errors. <a href="%s">Edit microplugin</a>'),
                    $post->ID, $post->post_title, $edit_post_link );
            } else {
                $type = 'warning';
                $message = sprintf( __('The %d microplugin with title "%s" has warnings. <a href="%s">Edit microplugin</a>'),
                    $post->ID, $post->post_title, $edit_post_link );
            }

            self::add_admin_notice($message, $type);
        }
    }

    /**
     * Maneja los errores producidos por los microplugins a nivel del intérprete de PHP.
     *
     * @param  string $type
     * @param  string $message
     * @param  string $file
     * @param  integer $line
     * @return null|false
     */
    public static function error_handler($type, $message, $file, $line)
    {
        $error = array(
            'type'    => $type,
            'message' => $message,
            'file'    => $file,
            'line'    => $line,
            )
        ;

        if (true === self::is_microplugin_error($error)) {

            $func = function() use ($error) {
                Microplugins::process_mircoplugin_error($error);
            };

            add_action('init', $func);

        } else {
            // Continúa con el manejador de errores por defecto.
            return false;
        }
    }

    /**
     * Chequea los accesos de lectura y escritura sobre el directorio 'cache'.
     *
     * @return boolean
     */
    public static function check_file_permissions()
    {
        return is_writable(MICROPLUGINS_CACHE_DIR) && is_readable(MICROPLUGINS_CACHE_DIR);
    }

    /**
     * Se ejecuta solo cuando se activa el plugin.
     *
     * Asigna las capacidades para manipular los microplugins a los roles
     * que dispongan de la capacidad "manage_options".
     *
     * @return null
     */
    public static function activation_hook()
    {
        global $wp_roles;

        $capabilities = array(
            'delete_microplugins', 'delete_others_microplugins', 'delete_private_microplugins',
            'delete_published_microplugins', 'edit_microplugins', 'edit_others_microplugins',
            'edit_private_microplugins', 'edit_published_microplugins','publish_microplugins', 'read_private_microplugins'
            )
        ;

        foreach( $wp_roles->roles as $role_id => $role_args ) {
            if (true === isset($role_args['capabilities']['manage_options']) && true == $role_args['capabilities']['manage_options']) {
                $role = get_role( $role_id );
                foreach ($capabilities as $cap) {
                    $role->add_cap( $cap );
                }
            }
        }
    }

    protected static function get_ace_themes()
    {
        $themes = array();

        $bright['chrome']          = array( 'id' => 'chrome', 'name' => 'Chrome' );
        $bright['clouds']          = array( 'id' => 'clouds', 'name' => 'Clouds' );
        $bright['crimson_editor']  = array( 'id' => 'crimson_editor', 'name' => 'Crimson Editor' );
        $bright['dawn']            = array( 'id' => 'dawn', 'name' => 'Dawn' );
        $bright['dreamweaver']     = array( 'id' => 'dreamweaver', 'name' => 'Dreamweaver' );
        $bright['eclipse']         = array( 'id' => 'eclipse', 'name' => 'Eclipse' );
        $bright['github']          = array( 'id' => 'github', 'name' => 'GitHub' );
        $bright['iplastic']        = array( 'id' => 'iplastic', 'name' => 'IPlastic' );
        $bright['solarized_light'] = array( 'id' => 'solarized_light', 'name' => 'Solarized Light' );
        $bright['textmate']        = array( 'id' => 'textmate', 'name' => 'TextMate' );
        $bright['tomorrow']        = array( 'id' => 'tomorrow', 'name' => 'Tomorrow' );
        $bright['xcode']           = array( 'id' => 'xcode', 'name' => 'XCode' );
        $bright['kuroir']          = array( 'id' => 'kuroir', 'name' => 'Kuroir' );
        $bright['katzenmilch']     = array( 'id' => 'katzenmilch', 'name' => 'KatzenMilch' );
        $bright['sqlserver']       = array( 'id' => 'sqlserver', 'name' => 'SQL Server' );

        $dark['ambiance']                = array( 'id' => 'ambiance', 'name' => 'Ambiance' );
        $dark['chaos']                   = array( 'id' => 'chaos', 'name' => 'Chaos' );
        $dark['clouds_midnight']         = array( 'id' => 'clouds_midnight', 'name' => 'Clouds Midnight' );
        $dark['cobalt']                  = array( 'id' => 'cobalt', 'name' => 'Cobalt' );
        // $dark['gruvbox']                 = array( 'id' => 'gruvbox', 'name' => 'Gruvbox' );
        $dark['idle_fingers']            = array( 'id' => 'idle_fingers', 'name' => 'idle Fingers' );
        $dark['kr_theme']                = array( 'id' => 'kr_theme', 'name' => 'krTheme' );
        $dark['merbivore']               = array( 'id' => 'merbivore', 'name' => 'Merbivore' );
        $dark['merbivore_soft']          = array( 'id' => 'merbivore_soft', 'name' => 'Merbivore Soft' );
        $dark['mono_industrial']         = array( 'id' => 'mono_industrial', 'name' => 'Mono Industrial' );
        $dark['monokai']                 = array( 'id' => 'monokai', 'name' => 'Monokai' );
        $dark['pastel_on_dark']          = array( 'id' => 'pastel_on_dark', 'name' => 'Pastel on dark' );
        $dark['solarized_dark']          = array( 'id' => 'solarized_dark', 'name' => 'Solarized Dark' );
        $dark['terminal']                = array( 'id' => 'terminal', 'name' => 'Terminal' );
        $dark['tomorrow_night']          = array( 'id' => 'tomorrow_night', 'name' => 'Tomorrow Night' );
        $dark['tomorrow_night_blue']     = array( 'id' => 'tomorrow_night_blue', 'name' => 'Tomorrow Night Blue' );
        $dark['tomorrow_night_bright']   = array( 'id' => 'tomorrow_night_bright', 'name' => 'Tomorrow Night Bright' );
        $dark['tomorrow_night_eighties'] = array( 'id' => 'tomorrow_night_eighties', 'name' => 'Tomorrow Night 80s' );
        $dark['twilight']                = array( 'id' => 'twilight', 'name' => 'Twilight' );
        $dark['vibrant_ink']             = array( 'id' => 'vibrant_ink', 'name' => 'Vibrant Ink' );

        $themes['bright'] = $bright;
        $themes['dark'] = $dark;

        return $themes;
    }

    /**
     * Registra las taxonomías para el tipo de entrada 'microplugin'.
     *
     * @return null
     */
    public static function register_taxonomies()
    {
        // Categories
        //

        $labels = array(
            'name'              => _x( 'Categories', 'Categories', 'microplugins' ),
            'singular_name'     => _x( 'Category', 'Category', 'microplugins' ),
            'search_items'      => __( 'Search Categories', 'microplugins' ),
            'all_items'         => __( 'All Categories', 'microplugins' ),
            'parent_item'       => __( 'Parent Category', 'microplugins' ),
            'parent_item_colon' => __( 'Parent Category:', 'microplugins' ),
            'edit_item'         => __( 'Edit Category', 'microplugins' ),
            'update_item'       => __( 'Update Category', 'microplugins' ),
            'add_new_item'      => __( 'Add New Category', 'microplugins' ),
            'new_item_name'     => __( 'New Category Name', 'microplugins' ),
            'menu_name'         => __( 'Categories', 'microplugins' ),
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'microplugin-category' ),
        );

        register_taxonomy( 'microplugin-category', array( self::POST_TYPE ), $args );

        // Tags
        //

        $labels = array(
            'name'                       => _x( 'Tags', 'Tags', 'microplugins' ),
            'singular_name'              => _x( 'Tag', 'Tag', 'microplugins' ),
            'search_items'               => __( 'Search Tags', 'microplugins' ),
            'popular_items'              => __( 'Popular Tags', 'microplugins' ),
            'all_items'                  => __( 'All Tags', 'microplugins' ),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __( 'Edit Tag', 'microplugins' ),
            'update_item'                => __( 'Update Tag', 'microplugins' ),
            'add_new_item'               => __( 'Add New Tag', 'microplugins' ),
            'new_item_name'              => __( 'New Tag Name', 'microplugins' ),
            'separate_items_with_commas' => __( 'Separate tags with commas', 'microplugins' ),
            'add_or_remove_items'        => __( 'Add or remove tags', 'microplugins' ),
            'choose_from_most_used'      => __( 'Choose from the most used tags', 'microplugins' ),
            'not_found'                  => __( 'No tags found.', 'microplugins' ),
            'menu_name'                  => __( 'Tags', 'microplugins' ),
        );

        $args = array(
            'hierarchical'          => false,
            'labels'                => $labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'update_count_callback' => '_update_post_term_count',
            'query_var'             => true,
            'rewrite'               => array( 'slug' => 'microplugin-tag' ),
        );

        register_taxonomy( 'microplugin-tag', self::POST_TYPE, $args );
    }
}