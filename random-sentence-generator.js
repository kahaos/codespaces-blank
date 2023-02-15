// Attach the AJAX callback functions to the button click events
jQuery(document).ready(function($) {
    $('#refresh_button').on('click', function() {
        get_random_sentence(tweet_random_sentence_vars.random_sentence_nonce);
    });
    $('#send_to_twitter_button').on('click', function() {
        send_to_twitter();
    });

    // AJAX callback function to get a random sentence from the server
    function get_random_sentence(nonce) {
        $.ajax({
            type: 'POST',
            url: tweet_random_sentence_vars.ajaxurl,
            data: {
                action: 'get_random_sentence',
                nonce: nonce
            },
            success: function(response) {
                // Update the text area with the random sentence
                $('#random_sentence_textarea').val(response.data);
            },
            error: function(xhr, status, error) {
                console.log('Error: ' + error);
            }
        });
    }

    // AJAX callback function to send the current sentence to Twitter
    function send_to_twitter() {
        // Get the current sentence
        var sentence = $('#random_sentence_textarea').val();
        // Create the tweet URL
        var tweet_url = "https://twitter.com/intent/tweet?text=" + encodeURIComponent(sentence);

        // Open the tweet URL in a new window
        window.open(tweet_url, "_blank");

        alert("The sentence has been sent to Twitter.");
    }
});
