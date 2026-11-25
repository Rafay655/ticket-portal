# VPS storage and voice-note directories

Git includes the required empty directory structure through .gitignore placeholders. Runtime logs, cached views, sessions, local backups, private keys and uploaded recordings remain excluded.

Voice recordings are saved in public/comment_attachment/audios. Comment files are saved in public/comment_attachment/files. These paths are separate from storage/app/public; storage:link does not create or migrate these audio uploads.

After pulling on the VPS, run from the project directory:

~~~bash
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache public/comment_attachment/audios public/comment_attachment/files
chmod -R ug+rwX storage bootstrap/cache public/comment_attachment/audios public/comment_attachment/files
php artisan optimize:clear
~~~

Ensure the PHP/web-server user belongs to the owning group or has equivalent write access. Do not use world-writable permissions.

Existing local recordings are not included in the Git deployment. If they must be migrated, copy them separately over SSH/SFTP to the same paths alongside the corresponding database records. Keep existing VPS uploads when pulling code; do not run git clean -fdx against a live deployment.
