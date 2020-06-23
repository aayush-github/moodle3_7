/**
 * Created by Laitkor.
 * Author: Devashish Srivastava
 */

require(['jquery', 'jqueryui'], function($) {
    $(document).ready(function() {
        
        $(':input[type="submit"]').prop('disabled', true);

        $('#iagree').click(function(e){
            var isChecked = $('#iagree').prop('checked');
            if (!isChecked == false) {

                $(':input[type="submit"]').prop('disabled', false);
            }else{
                $(':input[type="submit"]').prop('disabled', true);
            }
        });

    });
});