Todo list:

PSR-4 Loading

- NF: Main class
-- Init
-- Router
-- Front\Actions Class
-- Front\Filters Class
-- Admin\Actions
-- Admin\Filters


NF_Woo
- Front\Actions
- Front\Filters
- Admin\Actions
- Admin\Filters


NF_ACF
- PageWidget: Default Widgets set (Mogu iz page_widgets fields isčupat koje Post typeove podržava)
- Options: Default Options set


NF_Post
- WP_Post wrapper + interface


NF_Tax
- Taxonomy wrapper + interface


NF_Attachment Class
- WP Attachment Post object Attach wrapper + interface


NF_User
- WP_User extend + interface


NF_AJAX
- Streamlined form processing


NF_Upload
- Class for rendering upload field and handling upload


NF_Cron
- Class for managing cron jobs


NF_Email
- Class for sending email


NF_Alert
- Class for dislpaying alert messages to user


NF_Log
- Class for logging events in database
- https://github.com/katzgrau/KLogger/blob/master/src/Logger.php


NF
- Helper functions / Quick access to functions






PHP Dependencies:
- Gump Validation
- ACF Pro


JS Dependencies
- parsleyjs Validation


Page Widgets:
- Register
- Login
- Password Reset


Views:
- NF_Entry: index/view/edit
- NF_Tax: index/view/edit
- NF_User: index/view/edit
- NF_Log: index