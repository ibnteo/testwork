<?php
class DB {
	const DateFormat = 'Y-m-d H:i:s';

	private static $db;
	static function init($dbname, $username, $passwd, $host = 'localhost') {
		$options = [];
		$options[PDO::ATTR_DEFAULT_FETCH_MODE] = PDO::FETCH_ASSOC;
		$options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
		$options[PDO::ATTR_EMULATE_PREPARES] = true;
		try {
			self::$db = new PDO('mysql:host='.($host ?? 'localhost').';dbname='.$dbname.';charset=utf8', $username, $passwd, $options);
		} catch (PDOException $exception) {
			$err = ['TYPE'=>1, 'FILE'=>$exception->getFile(), 'LINE'=>$exception->getLine(), 'MESSAGE'=>$exception->getMessage()];
			throw new Exception(json_encode($err));
			exit;
		}
		self::$db->exec('SET CHARACTER SET utf8');
		self::$db->exec('SET NAMES utf8');
	}

	private static function prepare(array $args) {
		$sql = array_shift($args);
		$stmt = self::$db->prepare($sql);
		if (isset($args[0]) && is_array($args[0])) {
			$args = $args[0];
		}
		$stmt->execute($args);
		return $stmt;
	}
	
	static function beginTransaction(): bool {
		return self::$db->beginTransaction();
	}

	static function commit(): bool {
		return self::$db->commit();
	}

	static function rollBack(): bool {
		return self::$db->rollBack();
	}

	static function lastInsertId(?string $name = null): string {
		return self::$db->lastInsertId($name);
	}

	static function exec(...$args) {
		if (count($args) == 1 && is_array($args[0])) {
			$args = self::select(...$args[0]);
		}
		return self::prepare($args);
	}
		
	static function one(...$args): array {
		if (count($args) == 1 && is_array($args[0])) {
			$args = self::select(...$args[0]);
		}
		$rows = self::prepare($args);
		$row = $rows->fetch();
		if ($row == false) $row = [];
		return $row;
	}
		
	static function value(...$args) {
		if (count($args) == 1 && is_array($args[0])) {
			$args = self::select(...$args[0]);
		}
		$rows = self::prepare($args);
		return $rows->fetchColumn();
	}

	static function any(...$args): array {
		if (count($args) == 1 && is_array($args[0])) {
			$args = self::select(...$args[0]);
		}
		$result = [];
		$rows = self::prepare($args);
		$i = 0;
		while ($row = $rows->fetch()) {
			$result[] = $row;
			$i ++;
			if ($i >= 10000) break;
		}
		return $result;
	}

	private static function select(string $table, array $where = [], array $order = [], array $select = ['*'], array $limit = []): array {
		if ($select == ['*']) $select = ["`$table`.*"];
		$args = [];
		$sql = 'SELECT ';
		$sql .= implode(',', $select);
		$sql .= " FROM `$table`";
		$sql .= self::where($where, $args);
		if ($order) $sql .= ' ORDER BY '.implode(',', $order);
		if ($limit) $sql .= ' LIMIT '.implode(',', $limit);
		array_unshift($args, $sql);
		return $args;
	}

	private static function where($where, &$args) {
		$w = '';
		foreach ($where as $name=>$val) {
			$op = '=';
			if (is_numeric($name)) {
				@list($name, $op, $val) = $val;
			}
			$w .= $w ? ' AND ' : ' WHERE ';
			if ($op == 'NOT IS NULL') {
				$w .= "NOT `$name` IS NULL";
			} elseif ($op == 'IS NULL') {
				$w .= "`$name` IS NULL";
			} elseif ($op == 'IN') {
				$vs = [];
				foreach ($val as $v) {
					$vs[] = '?';
					$args[] = $v;
				}
				$vss = implode(',', $vs);
				$w .= "`$name` IN ($vss)";
			} elseif ($op == 'NOT IN') {
				$vs = [];
				foreach ($val as $v) {
					$vs[] = '?';
					$args[] = $v;
				}
				$vss = implode(',', $vs);
				$w .= "NOT `$name` IN ($vss)";
			} elseif ($op == 'EXISTS' || $op == 'NOT EXISTS') {
				$w .= "$op $name";
				if (! is_null($val)) $args[] = $val;
			} else {
				$w .= "`$name` $op ?";
				$args[] = $val;
			}
		}
		return $w;
	}

	static function count(string $table, array $where = []): int {
		$args = self::select($table, $where, [], ['COUNT(*)']);
		return intval(self::field(...$args));
	}

	static function delete(string $table, array $where): bool {
		if (! $where) return false;
		$sql = "DELETE FROM `$table` ";
		$args = [];
		$w = '';
		foreach ($where as $key=>$value) {
			if (is_numeric($key)) {
				list($name, $op, $val) = $value;
				$w .= $w ? ' AND ' : ' WHERE ';
				$w .= "`$name` $op ?";
				$args[] = $val;
			} else {
				$w .= $w ? ' AND ' : ' WHERE ';
				$w .= "`$key`=?";
				$args[] = $value;
			}
		}
		$sql .= $w;
		$stmt = self::$db->prepare($sql);
		return $stmt->execute($args);
	}
	
	static function replace(string $table, array $data): bool {
		$sql = "REPLACE INTO `$table` (";
		$values = ' VALUES (';
		$args = [];
		$i = 0;
		foreach ($data as $key=>$val) {
			$sql .= ($i ? ', ' : '') . '`'.$key.'`';
			$values .= ($i ? ', ' : '') . '?';
			$args[] = $val;
			$i ++;
		}
		$sql .= ')'.$values.')';
		$stmt = self::$db->prepare($sql);
		return $stmt->execute($args);
	}

	static function insert(string $table, array $data): int {
		$sql = "INSERT INTO `$table` (";
		$values = ' VALUES (';
		$args = [];
		$i = 0;
		foreach ($data as $key=>$val) {
			$sql .= ($i ? ', ' : '') . '`'.$key.'`';
			$values .= ($i ? ', ' : '') . '?';
			$args[] = $val;
			$i ++;
		}
		$sql .= ')'.$values.')';
		$stmt = self::$db->prepare($sql);
		$stmt->execute($args);
		$id = self::$db->lastInsertId();
		return $id;
	}

	static function update(string $table, array $data, string $idName='id', $id): int {
		$sql = "UPDATE `$table` SET ";
		$args = [];
		$i = 0;
		foreach ($data as $key=>$val) {
			$sql .= ($i ? ', ' : '') . '`'.$key.'`=?';
			$args[] = $val;
			$i ++;
		}
		$args[] = $id;
		$sql .= " WHERE `$idName`=?";
		$stmt = self::$db->prepare($sql);
		return $stmt->execute($args);
	}
}
