<?php
/**
 * Template Name: Radni Nalozi
 * Template Post Type: page
 * 
 * WordPress page template za prikaz radnih naloga.
 * Ovaj template prikazuje radne naloge samo ulogovanim korisnicima.
 * 
 * @package Radni_Nalozi
 */

if (!defined('ABSPATH')) {
    exit;
}

$rn_login_error = '';

if (!is_user_logged_in() && isset($_POST['rn_frontend_login']) && wp_verify_nonce($_POST['rn_login_nonce'], 'rn_frontend_login')) {
    $username = sanitize_user($_POST['rn_username']);
    $password = $_POST['rn_password'];
    
    $user = wp_signon(array(
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => isset($_POST['rn_remember'])
    ), is_ssl());
    
    if (!is_wp_error($user)) {
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, isset($_POST['rn_remember']));
        wp_safe_redirect(get_permalink());
        exit;
    } else {
        $rn_login_error = $user->get_error_message();
    }
}

get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        
        <?php while (have_posts()) : the_post(); ?>
            
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                
                <header class="entry-header">
                    <?php the_title('<h1 class="entry-title">', '</h1>'); ?>
                </header>
                
                <div class="entry-content">
                    <?php
                    if (is_user_logged_in()) {
                        echo do_shortcode('[radni_nalozi]');
                    } else {
                        ?>
                        <div class="rn-login-required">
                            <div class="rn-login-message">
                                <h2><?php _e('Potrebna je prijava', 'radni-nalozi'); ?></h2>
                                <p><?php _e('Morate biti prijavljeni da biste pristupili radnim nalozima.', 'radni-nalozi'); ?></p>
                            </div>
                            
                            <?php
                            $redirect_url = get_permalink();
                            
                            if (!empty($rn_login_error)) {
                                echo '<div class="rn-login-error">';
                                echo '<p>' . esc_html($rn_login_error) . '</p>';
                                echo '</div>';
                            }
                            ?>
                            
                            <form method="post" class="rn-login-form-template">
                                <?php wp_nonce_field('rn_frontend_login', 'rn_login_nonce'); ?>
                                
                                <div class="rn-form-field">
                                    <label for="rn_username"><?php _e('Korisničko ime ili email', 'radni-nalozi'); ?></label>
                                    <input type="text" name="rn_username" id="rn_username" required>
                                </div>
                                
                                <div class="rn-form-field">
                                    <label for="rn_password"><?php _e('Lozinka', 'radni-nalozi'); ?></label>
                                    <input type="password" name="rn_password" id="rn_password" required>
                                </div>
                                
                                <div class="rn-form-field rn-remember">
                                    <label>
                                        <input type="checkbox" name="rn_remember" value="1">
                                        <?php _e('Zapamti me', 'radni-nalozi'); ?>
                                    </label>
                                </div>
                                
                                <div class="rn-form-field">
                                    <button type="submit" name="rn_frontend_login" value="1" class="rn-login-button">
                                        <?php _e('Prijavi se', 'radni-nalozi'); ?>
                                    </button>
                                </div>
                                
                                <?php if (get_option('users_can_register')): ?>
                                    <div class="rn-form-links">
                                        <a href="<?php echo esc_url(wp_registration_url()); ?>"><?php _e('Registracija', 'radni-nalozi'); ?></a>
                                        <span class="rn-separator">|</span>
                                        <a href="<?php echo esc_url(wp_lostpassword_url($redirect_url)); ?>"><?php _e('Zaboravljena lozinka?', 'radni-nalozi'); ?></a>
                                    </div>
                                <?php else: ?>
                                    <div class="rn-form-links">
                                        <a href="<?php echo esc_url(wp_lostpassword_url($redirect_url)); ?>"><?php _e('Zaboravljena lozinka?', 'radni-nalozi'); ?></a>
                                    </div>
                                <?php endif; ?>
                            </form>
                        </div>
                        
                        <style>
                            .rn-login-required {
                                max-width: 400px;
                                margin: 40px auto;
                                padding: 30px;
                                background: #fff;
                                border-radius: 8px;
                                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                            }
                            .rn-login-message {
                                text-align: center;
                                margin-bottom: 25px;
                            }
                            .rn-login-message h2 {
                                margin: 0 0 10px 0;
                                font-size: 24px;
                                color: #1f2937;
                            }
                            .rn-login-message p {
                                margin: 0;
                                color: #6b7280;
                            }
                            .rn-login-error {
                                background: #fee2e2;
                                color: #991b1b;
                                padding: 12px 15px;
                                border-radius: 6px;
                                margin-bottom: 20px;
                                border: 1px solid #fecaca;
                            }
                            .rn-login-error p {
                                margin: 0;
                            }
                            .rn-login-form-template .rn-form-field {
                                margin-bottom: 20px;
                            }
                            .rn-login-form-template label {
                                display: block;
                                margin-bottom: 6px;
                                font-weight: 500;
                                color: #374151;
                            }
                            .rn-login-form-template input[type="text"],
                            .rn-login-form-template input[type="password"] {
                                width: 100%;
                                padding: 12px 14px;
                                border: 1px solid #d1d5db;
                                border-radius: 6px;
                                font-size: 15px;
                                transition: border-color 0.2s, box-shadow 0.2s;
                            }
                            .rn-login-form-template input[type="text"]:focus,
                            .rn-login-form-template input[type="password"]:focus {
                                outline: none;
                                border-color: #2563eb;
                                box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
                            }
                            .rn-login-form-template .rn-remember label {
                                display: flex;
                                align-items: center;
                                gap: 8px;
                                font-weight: normal;
                                cursor: pointer;
                            }
                            .rn-login-form-template .rn-remember input[type="checkbox"] {
                                width: 16px;
                                height: 16px;
                            }
                            .rn-login-button {
                                width: 100%;
                                padding: 12px 24px;
                                background: #2563eb;
                                color: #fff;
                                border: none;
                                border-radius: 6px;
                                font-size: 16px;
                                font-weight: 500;
                                cursor: pointer;
                                transition: background-color 0.2s;
                            }
                            .rn-login-button:hover {
                                background: #1d4ed8;
                            }
                            .rn-form-links {
                                text-align: center;
                                margin-top: 20px;
                                padding-top: 20px;
                                border-top: 1px solid #e5e7eb;
                            }
                            .rn-form-links a {
                                color: #2563eb;
                                text-decoration: none;
                            }
                            .rn-form-links a:hover {
                                text-decoration: underline;
                            }
                            .rn-separator {
                                color: #9ca3af;
                                margin: 0 10px;
                            }
                        </style>
                        <?php
                    }
                    ?>
                </div>
                
            </article>
            
        <?php endwhile; ?>
        
    </main>
</div>

<?php
get_sidebar();
get_footer();
