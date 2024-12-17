<?php
/**
 * Created by IntelliJ IDEA.
 * User: Nikolay Chervyakov 
 * Date: 08.09.2014
 * Time: 11:27
 */


namespace App\Installation\Step;


class DBSettingsStep extends AbstractStep
{
    protected $template = 'installation/dbsettings';

    protected $host;

    protected $port = 3306;

    protected $user;

    protected $password;

    protected $db;

    protected $createIfNotExists = false;

    protected $useExistingPassword;

    protected $defaultPassword;

    protected function processRequest(array $data = [])
    {
        $this->isValid = false;

        $this->useExistingPassword = (boolean) $data['use_existing_password'];
        $this->host = $data['host'];
        $this->port = $data['port'];
        $this->user = $data['user'];
        $this->password = $this->useExistingPassword ? $this->defaultPassword : $data['password'];
        $this->db = $data['db'];
        $this->createIfNotExists = $data['create_if_not_exists'];

        if (!$data['host']) {
            $this->errors[] = 'Please enter the host name.';
        }

        if (!$data['port']) {
            $this->errors[] = 'Please enter the port.';
        }

        if (!$data['user']) {
            $this->errors[] = 'Please enter the username.';
        }

        if (!$data['db']) {
            $this->errors[] = 'Please enter the DB name.';
        }

        if (count($this->errors)) {
            return false;
        }

        try {
            $dsn = "mysql:host={$this->host};port={$this->port}";
            $conn = new \PDO($dsn, $this->user, $this->password);
            $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            try {
                $stmt = $conn->prepare("USE `{$this->db}`");
                $stmt->execute();
            } catch (\PDOException $e) {
                // If the database doesn't exist and `createIfNotExists` is true, create it
                if ($this->createIfNotExists) {
                    $createStmt = $conn->prepare("CREATE DATABASE `{$this->db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
                    $createStmt->execute();
                } else {
                    throw new \Exception("Database '{$this->db}' does not exist, and creation is disabled.");
                }
            }


        } catch (\Exception $e) {
            $this->errors[] = "Error " . $e->getCode() . ": " . $e->getMessage();
            return false;
        }

        $this->isValid = true;
        return true;
    }

    protected function persistFields()
    {
        return ['host', 'port', 'user', 'password', 'db', 'createIfNotExists', 'useExistingPassword', 'defaultPassword'];
    }

    public function init()
    {
        $this->pixie->config->load_inherited_group('db');
        $config = $this->pixie->config->get_group('db');

        $this->host = $config['default']['host'];
        $this->port = $config['default']['port'];
        $this->user = $config['default']['user'];
        $this->password = $config['default']['password'];
        $this->db = $config['default']['db'];

        $this->defaultPassword = $this->password;
    }

    public function getViewData()
    {
        return [
            'host' => $this->host,
            'port' => $this->port,
            'user' => $this->user,
            'password' => $this->password,
            'db' => $this->db,
            'create_if_not_exists' => $this->createIfNotExists,
            'use_existing_password' => $this->useExistingPassword,
        ];
    }
} 