<?php
namespace CyberKavach\Core;

use CyberKavach\Core\Database;
use PDO;

class RBAC
{
    private PDO $db;
    private array $permissionsMap;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $map = require __DIR__ . '/../../config/permissions.php';
        $this->permissionsMap = is_array($map) ? $map : [];
    }

    /**
     * Returns the configured permission map.
     *
     * @return array<string, array<int, string>>
     */
    public function permissionMap(): array
    {
        return $this->permissionsMap;
    }

    /**
     * Checks whether the configured permission map contains a module/action pair.
     */
    public function supports(string $module, string $action): bool
    {
        return isset($this->permissionsMap[$module]) && in_array($action, $this->permissionsMap[$module], true);
    }

    /**
     * Returns array of role ids assigned to a user (active only)
     * @param int $userId
     * @return array<int>
     */
    public function getUserRoleIds(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT role_id FROM user_roles WHERE user_id = :uid AND is_active = 1');
        $stmt->execute([':uid' => $userId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * Checks whether a user has a given permission (module + action)
     * @param int $userId
     * @param string $module
     * @param string $action
     * @return bool
     */
    public function hasPermission(int $userId, string $module, string $action): bool
    {
        if (!$this->supports($module, $action)) {
            return false;
        }

        $roleIds = $this->getUserRoleIds($userId);
        if (empty($roleIds)) {
            return false;
        }

        // build placeholders
        $placeholders = implode(',', array_fill(0, count($roleIds), '?'));

        $sql = "SELECT COUNT(1) FROM role_permissions rp
                JOIN permissions p ON p.id = rp.permission_id
                WHERE rp.role_id IN ($placeholders) AND p.module = ? AND p.action = ?";

        $params = array_merge($roleIds, [$module, $action]);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $count = (int)$stmt->fetchColumn();
        return $count > 0;
    }

    /**
     * Checks whether a user has any permission from a list of module/action pairs.
     *
     * @param array<int, array{0:string,1:string}> $checks
     */
    public function hasAnyPermission(int $userId, array $checks): bool
    {
        foreach ($checks as $check) {
            if (count($check) !== 2) {
                continue;
            }

            [$module, $action] = $check;
            if ($this->hasPermission($userId, $module, $action)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Throws if the permission does not exist or the user does not have it.
     */
    public function assertPermission(int $userId, string $module, string $action): void
    {
        if (!$this->hasPermission($userId, $module, $action)) {
            throw new \RuntimeException('Forbidden');
        }
    }

    /**
     * Returns the highest role level for a user (useful for approval chains)
     * @param int $userId
     * @return int
     */
    public function getHighestRoleLevel(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT MAX(r.level) FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = :uid AND ur.is_active = 1');
        $stmt->execute([':uid' => $userId]);
        $lvl = $stmt->fetchColumn();
        return $lvl ? (int)$lvl : 0;
    }
}
