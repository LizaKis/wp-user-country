<?php
/**
 * Plugin Name: User's country
 * Description: Adds country to user table after registration
 * Author: Liza K <liza@bondarev.org>
 * Version: 1.0
 * License: MIT
 */

// Hooks after pmpro member registration
add_action('pmpro_after_checkout', 'add_user_country');

// Hooks after usual user registration
add_action('user_register', 'add_user_country', 10, 1);

// Hooks near the bottom of profile page (if current user)
add_action('show_user_profile', 'custom_user_profile_fields');

// Hooks near the bottom of the profile page (if not current user)
add_action('edit_user_profile', 'custom_user_profile_fields');

// Add new column to user table
add_filter('manage_users_columns', 'user_country_column');
add_action('manage_users_custom_column', 'user_country_column_value', 10, 3);

/**
 * Guess User country by IP address
 * @param string $ip XXX.XXX.XXX.XXX
 * @return string coutry or IP address
 */
function get_country_by_ip($ip)
{
    $url = "http://ipinfo.io/{$ip}";

    $resp = file_get_contents($url);
    file_put_contents('/tmp/ipinfo.resp.txt', $resp);
    $data     = json_decode($resp, true);
    $location = [];
    if (isset($data['country'])) {
        $location[] = $data['country'];
    }
    if (isset($data['region'])) {
        $location[] = $data['region'];
    }

    if (count($location) == 0) {
        $location[] = $ip;
    }

    return implode(', ', $location);
}

/**
 * Add user country to user table
 * @param int $user_id
 */
function add_user_country($user_id)
{
    file_put_contents('/tmp/pmpro_after_checkout.txt', $user_id);

    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    $country = get_country_by_ip($_SERVER['REMOTE_ADDR']);

    update_usermeta($user_id, 'country', $country);
}

/**
 * Add country field to user profile view page in admin
 * @param WP_User $user
 */
function custom_user_profile_fields($user)
{
    ?>
    <table class="form-table">
        <tr>
            <th>
                <label for="code"><?php _e('Country');?></label>
            </th>
            <td>
                <?php echo esc_attr(get_the_author_meta('country', $user->ID)); ?>
            </td>
        </tr>
    </table>
<?php
}

function user_country_column($columns)
{
    $columns['country'] = 'Country';
    return $columns;
}

function user_country_column_value($value, $column_name, $user_id)
{
    $user = get_userdata($user_id);
    if ('country' == $column_name) {
        return get_the_author_meta('country', $user_id);
    }
    return $value;
}
