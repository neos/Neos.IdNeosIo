<?php
namespace TYPO3\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
	Doctrine\DBAL\Schema\Schema;

/**
 * Migrate users from TYPO3.Party (typo3_party_* tables) to new User domain model (neos_crowdclient_domain_model_user)
 */
class Version20160801122505 extends AbstractMigration {

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function up(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

		$this->addSql("CREATE TABLE neos_crowdclient_domain_model_user (persistence_object_identifier VARCHAR(40) NOT NULL, account VARCHAR(40) DEFAULT NULL, firstname VARCHAR(255) NOT NULL, lastname VARCHAR(255) NOT NULL, emailaddress VARCHAR(255) NOT NULL, INDEX IDX_D10B60717D3656A4 (account), PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
		$this->addSql("ALTER TABLE neos_crowdclient_domain_model_user ADD CONSTRAINT FK_D10B60717D3656A4 FOREIGN KEY (account) REFERENCES typo3_flow_security_account (persistence_object_identifier)");

		$existingUsersQuery = 'SELECT
		  ap.persistence_object_identifier id, a.persistence_object_identifier account, pn.firstname, pn.lastname, ea.identifier emailaddress
		FROM
		  typo3_party_domain_model_abstractparty ap
		  LEFT JOIN typo3_party_domain_model_person p ON p.persistence_object_identifier = ap.persistence_object_identifier
		  LEFT JOIN typo3_party_domain_model_abstractparty_accounts_join apa ON apa.party_abstractparty = ap.persistence_object_identifier
		  LEFT JOIN typo3_flow_security_account a ON a.persistence_object_identifier = apa.flow_security_account
		  LEFT JOIN typo3_party_domain_model_personname pn ON pn.persistence_object_identifier = p.name
		  LEFT JOIN typo3_party_domain_model_electronicaddress ea ON ea.persistence_object_identifier = p.primaryelectronicaddress';

		$existingUsersStatement = $this->connection->executeQuery($existingUsersQuery);
		while ($existingUserRow = $existingUsersStatement->fetch(\PDO::FETCH_ASSOC)) {
			$id = $this->connection->quote($existingUserRow['id']);
			$account = isset($existingUserRow['account']) ? $this->connection->quote($existingUserRow['account']) : null;
			$firstName = $this->connection->quote($existingUserRow['firstname']);
			$lastName = $this->connection->quote($existingUserRow['lastname']);
			$emailAddress = $this->connection->quote($existingUserRow['emailaddress']);
			$this->addSql("INSERT INTO neos_crowdclient_domain_model_user (persistence_object_identifier, account, firstname, lastname, emailaddress) VALUES ($id, $account, $firstName, $lastName, $emailAddress)");
		}

		$this->addSql('TRUNCATE TABLE typo3_party_domain_model_person_electronicaddresses_join');
		$this->addSql('TRUNCATE TABLE typo3_party_domain_model_person');
		$this->addSql('TRUNCATE TABLE typo3_party_domain_model_personname');
		$this->addSql('TRUNCATE TABLE typo3_party_domain_model_electronicaddress');
		$this->addSql('TRUNCATE TABLE typo3_party_domain_model_abstractparty_accounts_join');
		$this->addSql('TRUNCATE TABLE typo3_party_domain_model_abstractparty');
	}

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function down(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

		$this->addSql("DROP TABLE neos_crowdclient_domain_model_user");
	}
}