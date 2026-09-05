<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260905000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initiales KuTaWerk-Schema einschließlich der grundlegenden Bereiche, Ansprechpartner und Dokumente.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE departments (id INT AUTO_INCREMENT NOT NULL, slug VARCHAR(80) NOT NULL, name VARCHAR(120) NOT NULL, active TINYINT NOT NULL, UNIQUE INDEX UNIQ_16AEB8D4989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) DEFAULT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, roles JSON NOT NULL, permissions JSON NOT NULL, password VARCHAR(255) DEFAULT NULL, active TINYINT NOT NULL, trainer TINYINT NOT NULL, trainer_image_path VARCHAR(255) DEFAULT NULL, trainer_bio LONGTEXT DEFAULT NULL, contact_function VARCHAR(180) DEFAULT NULL, contact_person TINYINT NOT NULL, access_from DATETIME DEFAULT NULL, access_until DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_departments (user_id INT NOT NULL, department_id INT NOT NULL, INDEX IDX_BFB57141A76ED395 (user_id), INDEX IDX_BFB57141AE80F5DF (department_id), PRIMARY KEY (user_id, department_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_invitations (id INT AUTO_INCREMENT NOT NULL, token_hash VARCHAR(64) NOT NULL, expires_at DATETIME NOT NULL, accepted_at DATETIME DEFAULT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_8A3CD93BB3BC57DA (token_hash), INDEX IDX_8A3CD93BA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE locations (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(160) NOT NULL, street VARCHAR(160) NOT NULL, postal_code VARCHAR(20) NOT NULL, city VARCHAR(100) NOT NULL, notes LONGTEXT DEFAULT NULL, active TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE location_address_versions (id INT AUTO_INCREMENT NOT NULL, street VARCHAR(160) NOT NULL, postal_code VARCHAR(20) NOT NULL, city VARCHAR(100) NOT NULL, notes LONGTEXT DEFAULT NULL, valid_from DATE DEFAULT NULL, valid_until DATE DEFAULT NULL, location_id INT NOT NULL, INDEX IDX_96527B5764D218E (location_id), INDEX idx_location_address_validity (valid_from, valid_until), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE courses (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(160) NOT NULL, age_group VARCHAR(160) DEFAULT NULL, active TINYINT NOT NULL, valid_from DATE DEFAULT NULL, valid_until DATE DEFAULT NULL, department_id INT NOT NULL, INDEX IDX_A9A55A4CAE80F5DF (department_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE course_trainers (course_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_8911979D591CC992 (course_id), INDEX IDX_8911979DA76ED395 (user_id), PRIMARY KEY (course_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE training_sessions (id INT AUTO_INCREMENT NOT NULL, legacy_key VARCHAR(190) DEFAULT NULL, weekday INT NOT NULL, starts_at TIME NOT NULL, ends_at TIME NOT NULL, valid_from DATE DEFAULT NULL, valid_until DATE DEFAULT NULL, notes LONGTEXT DEFAULT NULL, dance_style VARCHAR(160) DEFAULT NULL, legacy_trainer_names VARCHAR(500) DEFAULT NULL, active TINYINT NOT NULL, course_id INT NOT NULL, location_id INT NOT NULL, UNIQUE INDEX UNIQ_7D058E8455573495 (legacy_key), INDEX IDX_7D058E84591CC992 (course_id), INDEX IDX_7D058E8464D218E (location_id), INDEX idx_training_weekday_time (weekday, starts_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE events (id INT AUTO_INCREMENT NOT NULL, legacy_key VARCHAR(190) DEFAULT NULL, title VARCHAR(160) NOT NULL, event_date DATE NOT NULL, event_time TIME DEFAULT NULL, location VARCHAR(160) DEFAULT NULL, description LONGTEXT DEFAULT NULL, link VARCHAR(2048) DEFAULT NULL, original_date_label VARCHAR(100) DEFAULT NULL, active TINYINT NOT NULL, visible_from DATETIME DEFAULT NULL, visible_until DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, department_id INT DEFAULT NULL, course_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_5387574A55573495 (legacy_key), INDEX IDX_5387574AAE80F5DF (department_id), INDEX IDX_5387574A591CC992 (course_id), INDEX IDX_5387574AB03A8386 (created_by_id), INDEX idx_events_date (event_date), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE news_posts (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(180) NOT NULL, slug VARCHAR(190) NOT NULL, legacy_key VARCHAR(190) DEFAULT NULL, image_path VARCHAR(255) DEFAULT NULL, excerpt LONGTEXT DEFAULT NULL, content LONGTEXT NOT NULL, content_is_html TINYINT NOT NULL, published TINYINT NOT NULL, published_at DATETIME NOT NULL, visible_from DATETIME DEFAULT NULL, visible_until DATETIME DEFAULT NULL, author_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_F2E23DA4989D9B62 (slug), UNIQUE INDEX UNIQ_F2E23DA455573495 (legacy_key), INDEX IDX_F2E23DA4F675F31B (author_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql("CREATE TABLE news_gallery_images (id INT AUTO_INCREMENT NOT NULL, image_path VARCHAR(255) NOT NULL, caption VARCHAR(180) DEFAULT NULL, position INT NOT NULL, gallery_key VARCHAR(36) DEFAULT 'main' NOT NULL, post_id INT NOT NULL, INDEX IDX_DD7655014B89032C (post_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('CREATE TABLE document_versions (id INT AUTO_INCREMENT NOT NULL, document_key VARCHAR(80) NOT NULL, stored_path VARCHAR(255) NOT NULL, original_name VARCHAR(255) NOT NULL, mime_type VARCHAR(100) NOT NULL, file_size INT NOT NULL, valid_from DATE DEFAULT NULL, valid_until DATE DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE user_departments ADD CONSTRAINT FK_BFB57141A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_departments ADD CONSTRAINT FK_BFB57141AE80F5DF FOREIGN KEY (department_id) REFERENCES departments (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_invitations ADD CONSTRAINT FK_8A3CD93BA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE location_address_versions ADD CONSTRAINT FK_96527B5764D218E FOREIGN KEY (location_id) REFERENCES locations (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE courses ADD CONSTRAINT FK_A9A55A4CAE80F5DF FOREIGN KEY (department_id) REFERENCES departments (id)');
        $this->addSql('ALTER TABLE course_trainers ADD CONSTRAINT FK_8911979D591CC992 FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course_trainers ADD CONSTRAINT FK_8911979DA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE training_sessions ADD CONSTRAINT FK_7D058E84591CC992 FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE training_sessions ADD CONSTRAINT FK_7D058E8464D218E FOREIGN KEY (location_id) REFERENCES locations (id)');
        $this->addSql('ALTER TABLE events ADD CONSTRAINT FK_5387574AAE80F5DF FOREIGN KEY (department_id) REFERENCES departments (id)');
        $this->addSql('ALTER TABLE events ADD CONSTRAINT FK_5387574A591CC992 FOREIGN KEY (course_id) REFERENCES courses (id)');
        $this->addSql('ALTER TABLE events ADD CONSTRAINT FK_5387574AB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE news_posts ADD CONSTRAINT FK_F2E23DA4F675F31B FOREIGN KEY (author_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE news_gallery_images ADD CONSTRAINT FK_DD7655014B89032C FOREIGN KEY (post_id) REFERENCES news_posts (id) ON DELETE CASCADE');

        $this->addSql("INSERT INTO departments (slug, name, active) VALUES ('dance', 'Tanz', 1), ('culture', 'Kultur', 1), ('technology', 'Technik', 1), ('kuta-lounge', 'KuTa Lounge', 1)");

        $contacts = [
            ['tanzsparte@kutawerk.de', 'Beatrice', 'Peana', 1, null, 'Ansprechpartner Tanzsparte', 'dance'],
            ['mager@kutawerk.de', 'Thorsten', 'Mager', 0, '/media/i9385343a4ebc08b7.jpg', 'Leiter Kultursparte', 'culture'],
            ['jonas@kutawerk.de', 'Uwe', 'Jonas', 0, '/media/i348db21cc8acb07e.jpg', 'Leiter Event- und Techniksparte', 'technology'],
            ['lounge@kutawerk.de', 'Emma', '', 0, '/media/i6be93c64609a0481.png', 'Koordination & Ansprechpartner KuTa Lounge', 'kuta-lounge'],
        ];
        foreach ($contacts as [$email, $firstName, $lastName, $trainer, $image, $function, $department]) {
            $this->addSql('INSERT INTO users (email, first_name, last_name, roles, permissions, password, active, trainer, trainer_image_path, trainer_bio, contact_function, contact_person, access_from, access_until) VALUES (?, ?, ?, ?, ?, NULL, 1, ?, ?, NULL, ?, 1, NULL, NULL)', [$email, $firstName, $lastName, '[]', '[]', $trainer, $image, $function]);
            $this->addSql('INSERT INTO user_departments (user_id, department_id) SELECT u.id, d.id FROM users u CROSS JOIN departments d WHERE u.email = ? AND d.slug = ?', [$email, $department]);
        }

        $documents = [
            ['association_statutes', '/downloads/8300398115-vereinssatzung.pdf', 'vereinssatzung.pdf', 375120],
            ['lounge_contract', '/downloads/8298657515-Mietvertrag-KuTa-Lounge-2023.pdf', 'Mietvertrag KuTa Lounge 2023.pdf', 168415],
            ['membership_application', '/downloads/8300399715-2024-09-27_Kuta-Mitgliedsantrag.pdf', '2024-09-27_Kuta Mitgliedsantrag.pdf', 134308],
            ['garde_application', '/downloads/8597501115-AntragMitgliedGardetanz2020.pdf', 'AntragMitgliedGardetanz2020.pdf', 222134],
            ['dance_contributions', '/downloads/8300399615-Beitragsordnung-2020-91-.pdf', 'Beitragsordnung 2020[91].pdf', 448296],
        ];
        foreach ($documents as [$key, $path, $name, $size]) {
            $this->addSql('INSERT INTO document_versions (document_key, stored_path, original_name, mime_type, file_size, valid_from, valid_until, created_at) VALUES (?, ?, ?, ?, ?, NULL, NULL, NOW())', [$key, $path, $name, 'application/pdf', $size]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE news_gallery_images');
        $this->addSql('DROP TABLE news_posts');
        $this->addSql('DROP TABLE events');
        $this->addSql('DROP TABLE training_sessions');
        $this->addSql('DROP TABLE course_trainers');
        $this->addSql('DROP TABLE courses');
        $this->addSql('DROP TABLE location_address_versions');
        $this->addSql('DROP TABLE locations');
        $this->addSql('DROP TABLE user_invitations');
        $this->addSql('DROP TABLE user_departments');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE departments');
        $this->addSql('DROP TABLE document_versions');
    }
}
