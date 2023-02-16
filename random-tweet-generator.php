<?php
/*
Plugin Name: Random Sentence Generator
Plugin URI: https://aitools2023.com/random-sentence-generator
Description: Generate a random sentence from your website's content and display it in a dashboard widget. Also, send the sentence to Twitter.
Version: 1.1
Author: KB
Author URI: https://aitools2023.com
*/

// Enqueue the custom JavaScript file random-sentence-generator.js
function enqueue_random_sentence_generator_scripts() {
    $data = array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce( 'random-sentence-generator-nonce' )
    );
    wp_enqueue_script( 'random-sentence-generator.js', plugin_dir_url( __FILE__ ) . 'random-sentence-generator.js', array(), '1.1', true );
    wp_localize_script( 'random-sentence-generator.js', 'random_sentence_generator_data', $data );
}
add_action( 'wp_enqueue_scripts', 'enqueue_random_sentence_generator_scripts' );
add_action( 'admin_enqueue_scripts', 'enqueue_random_sentence_generator_scripts' );
add_action( 'wp_footer', 'enqueue_random_sentence_generator_scripts' );

// Add a custom dashboard widget
function add_random_sentence_dashboard_widget() {
    wp_add_dashboard_widget(
        'random_sentence_dashboard_widget',
        'Random Sentence',
        'display_random_sentence_dashboard_widget'
    );
}
add_action( 'wp_dashboard_setup', 'add_random_sentence_dashboard_widget' );

// Display the random sentence dashboard widget
function display_random_sentence_dashboard_widget() {
    // Get the random sentence
    $sentence = get_random_sentence_from_website();

    // Extract relevant tags from the sentence
    $tags = extract_tags_from_sentence($sentence);

    // Construct the tweet
    $tweet = construct_tweet($sentence, $tags);

    // Display the sentence in a text area
    echo '<p class="random-sentence-display">' . $sentence . '</p>';
    echo '<textarea id="random_sentence" readonly>' . $sentence . '</textarea>';
    echo '<input type="hidden" id="random_sentence_security" value="' . wp_create_nonce('random-sentence-nonce') . '">';

    // Add a refresh button
    echo '<button id="refresh_button" type="button">Refresh</button>';

    // Add a send to Twitter button
    echo '<button id="send_to_twitter_button" type="button">Send to Twitter</button>';
}

// Construct a tweet from a sentence and its tags
function construct_tweet($sentence, $tags) {
    // Calculate the maximum length of the tweet
    $max_tweet_length = 280;

    // Subtract the length of the tags and URL from the maximum tweet length
    $url_length = 23; // The length of t.co URL after Twitter shortens it
    $tags_length = strlen(implode(' ', $tags));
    $available_tweet_length = $max_tweet_length - $url_length - $tags_length - 1; // Subtract 1 for the space between the sentence and the tags

    // Truncate the tweet if it exceeds the available length
    if (strlen($sentence) > $available_tweet_length) {
        $sentence = substr($sentence, 0, $available_tweet_length - 3) . '...';
    }

    // Add the tags to the tweet
    if ($tags_length > 0) {
        $sentence .= ' ' . implode(' ', $tags);
    }

    // Add the URL to the tweet
    $sentence .= ' ' . get_the_permalink();

    return $sentence;
}
function get_random_sentence_from_website() {
    $args = array(
        'post_type' => 'post',
        'orderby' => 'rand',
        'posts_per_page' => 1
    );

    $query = new WP_Query( $args );

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $content = get_the_content();
            $sentences = preg_split( '/(\.|!|\?)\s+/', $content, -1, PREG_SPLIT_DELIM_CAPTURE );
            $random_sentence = $sentences[array_rand($sentences)];
        }
    } else {
        $random_sentence = 'No sentences found.';
    }

    wp_reset_postdata();

    return $random_sentence;
}

// Add an AJAX action for getting a random sentence
add_action('wp_ajax_generate_random_sentence', 'generate_random_sentence');
add_action('wp_ajax_nopriv_generate_random_sentence', 'generate_random_sentence');

// Function to generate a random sentence
function generate_random_sentence() {
check_ajax_referer('random_sentence_generator_nonce', 'security');
$sentence = get_random_sentence_from_website();
wp_send_json_success($sentence);
wp_die();
}

// Function to extract relevant tags from a sentence
function extract_tags_from_sentence($sentence) {
// Extract all words that start with a hash (#)
preg_replace_callback('/#(\w+)/', $sentence, $matches);
$tags = $matches[1];
return $tags;
}

// Function to tweet a random sentence
function tweet_random_sentence() {
// Fetch a random sentence from the website
$sentence = get_random_sentence_from_website();
    // Extract relevant tags from the sentence
$tags = extract_tags_from_sentence($sentence);

// Construct the tweet
$tweet = $sentence;

// Calculate the maximum length of the tweet
$max_tweet_length = 280;

// Subtract the length of the tags and URL from the maximum tweet length
$url_length = 23; // The length of t.co URL after Twitter shortens it
$tags_length = strlen(implode(' ', $tags));
$available_tweet_length = $max_tweet_length - $url_length - $tags_length - 1; // Subtract 1 for the space between the sentence and the tags

// Truncate the tweet if it exceeds the available length
if (strlen($tweet) > $available_tweet_length) {
    $tweet = substr($tweet, 0, $available_tweet_length - 3) . '...';
}

// Add the tags to the tweet
if ($tags_length > 0) {
    $tweet .= ' ' . implode(' ', $tags);
}

// Add the URL to the tweet
$tweet .= ' ' . get_the_permalink();

return $tweet;
}

// Get the random sentence
$sentence = tweet_random_sentence();
$nonce = wp_create_nonce('random-sentence-nonce');
    // Display the sentence in a text area
echo '<textarea id=\'random_sentence\' readonly>' . $sentence . '</textarea><input type=\'hidden\' id=\'random_sentence_security\' value=\'' . $nonce . '\'>';

// Add a refresh button
echo '<button id=\'refresh_button\' type=\'button\'>Refresh</button>';

// Add a send to Twitter button
echo '<button id=\'send_to_twitter_button\' type=\'button\'>Send to Twitter</button>';

// Callback function for getting a random sentence via AJAX
function ajax_get_random_sentence() {
check_ajax_referer('random-sentence-nonce', 'nonce');
$sentence = tweet_random_sentence();
wp_send_json_success($sentence);
wp_die();
}


wp_localize_script('random-sentence-generator.js', 'random_sentence_generator_data', $data);


// Add hooks for enqueueing scripts and adding dashboard widgets
add_action('wp_enqueue_scripts', 'wp_footer');
add_action('admin_enqueue_scripts', 'enqueue_random_sentence_generator_scripts');
add_action('wp_dashboard_setup', 'add_random_sentence_dashboard_widget');

// Add hooks for AJAX actions
add_action('wp_ajax_generate_random_sentence', 'generate_random_sentence');
add_action('wp_ajax_nopriv_generate_random_sentence', 'generate_random_sentence');
add_action('wp_ajax_get_random_sentence', 'ajax_get_random');
