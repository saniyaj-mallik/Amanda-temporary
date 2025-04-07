jQuery(document).ready(function($) {
    $('#group-management-enrolled-users-datatable a').replaceWith(function(){
        return $('<div/>', {
            html: $(this).html()
        });
    });
	
	$('.complete-course-button').on('click', function (e) {
        e.preventDefault();

        // Show SweetAlert confirmation box
        Swal.fire({
            title: 'Are you sure?',
            text: "Please make sure you have completed the entire training before selecting this option. Once you click the button below, the completion date on your certificate will be recorded as today and cannot be altered.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, complete it!',
            cancelButtonText: 'No'
        }).then((result) => {
            if (result.isConfirmed) {
                // If the user clicks "Yes", send an AJAX request
                var course_id = $(this).data('course-id'); // Get course ID from button
                var user_id = $(this).data('user-id');

                $.ajax({
                    url: wdm_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'complete_learndash_course',
                        course_id: course_id,
                        user_id: user_id,
                        nonce: wdm_ajax.nonce
                    },
                    success: function (response) {
                        if (response.success) {
                            Swal.fire(
                                response.data.heading,
                                response.data.message,
                                'success'
                            ).then(() => {
                                // Redirect to the dashboard page after success
                                window.location.href = '/dashboard';
                            });
                        } else {
                            Swal.fire('Error', response.data, 'error');
                        }
                        console.log(response);
                    }
                });
            }
        });
    });
});
