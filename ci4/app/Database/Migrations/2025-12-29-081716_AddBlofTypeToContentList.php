<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBlofTypeToContentList extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        if (!$db->fieldExists('blog_type', 'content_list')) {
            $this->forge->addColumn('content_list', [
                'blog_type' => [
                    'type' => 'BOOLEAN',
                    'default' => false,
                    'null' => true,
                ],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('content_list', 'blog_type');
    }
}
