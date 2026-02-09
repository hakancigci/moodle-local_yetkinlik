define(['jquery', 'core/notification'], function($, Notification) {
    return {
        init: function() {
            // Event delegation kullanarak dinamik eklenen satırlarda da çalışmasını sağlayalım
            $(document).on('change', '.yetkinlik-select', function() {
                var dropdown = $(this);
                var competencyId = dropdown.val();
                var questionId = dropdown.data('questionid');
                var courseId = dropdown.data('courseid');

                // M.cfg.wwwroot zaten Moodle sayfalarında tanımlıdır
                var ajaxUrl = M.cfg.wwwroot + '/local/yetkinlik/ajax.php';

                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'save_mapping',
                        courseid: courseId,
                        questionid: questionId,
                        competencyid: competencyId,
                        sesskey: M.cfg.sesskey
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'ok' || response.status === 'deleted') {
                            // Başarılı bildirim efekti
                            dropdown.css('border-color', '#28a745');
                            setTimeout(function() {
                                dropdown.css('border-color', '');
                            }, 2000);
                        }
                    },
                    error: function(xhr, status, error) {
                        // Alert yerine Moodle'ın kendi bildirim sistemini kullanıyoruz (no-alert hatası için)
                        Notification.exception(new Error(error));
                    }
                });
            });
        }
    };
});
