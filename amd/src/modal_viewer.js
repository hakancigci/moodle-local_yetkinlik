// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Modal viewer for competency question review.
 *
 * @module     local_yetkinlik/modal_viewer
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/modal', 'core/str', 'core/notification'],
    function($, Modal, Str, Notification) {

    return {
        /**
         * Initialize the modal viewer for the given selector.
         *
         * @param {string} selector The CSS selector for the links (e.g., '.view-question-modal')
         */
        init: function(selector) {
            $(document).on('click', selector, function(e) {
                e.preventDefault();
                e.stopPropagation();

                var targetUrl = $(this).attr('href');

                // Fetch the translated string for the modal title.
                Str.get_string('viewattempt', 'local_yetkinlik').then(function(title) {

                    // Create and show modal using modern Moodle 5.x Modal class.
                    return Modal.create({
                        title: title,
                        body: '<iframe src="' + targetUrl + '" width="100%" height="600px" frameborder="0"></iframe>',
                        large: true,
                        show: true,
                        removeOnClose: true
                    });

                }).catch(Notification.exception);

                return false;
            });
        }
    };
});
