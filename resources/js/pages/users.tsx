import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import Heading from '@/components/heading';
import AppLayout from '@/layouts/app-layout';
import { type PaginatedResponse, type Role, type User } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { Edit2, Plus, Search, Trash2, Key } from 'lucide-react';
import { useState, useEffect } from 'react';
import axios from 'axios';
import UserModal from '@/components/user-modal';
import UserPasswordModal from '@/components/user-password-modal';

interface UsersPageProps {
    // These will be passed from the backend
}

export default function Users(props: UsersPageProps) {
    const [users, setUsers] = useState<PaginatedResponse<User> | null>(null);
    const [roles, setRoles] = useState<Role[]>([]);
    const [groups, setGroups] = useState<any[]>([]);
    const [managers, setManagers] = useState<User[]>([]);
    const [loading, setLoading] = useState(true);
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedRole, setSelectedRole] = useState<string>('');
    const [selectedGroup, setSelectedGroup] = useState<string>('');
    const [currentPage, setCurrentPage] = useState(1);
    const [perPage, setPerPage] = useState(10);
    
    // Modal state
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingUser, setEditingUser] = useState<User | null>(null);
    
    // Password modal state
    const [isPasswordModalOpen, setIsPasswordModalOpen] = useState(false);
    const [passwordUser, setPasswordUser] = useState<User | null>(null);
    
    const fetchUsers = async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams({
                page: currentPage.toString(),
                per_page: perPage.toString(),
            });

            if (searchTerm) {
                params.append('name', searchTerm);
                params.append('email', searchTerm);
            }
            if (selectedRole) {
                params.append('role_id', selectedRole);
            }
            if (selectedGroup) {
                params.append('group_id', selectedGroup);
            }

            console.log(`/api/users?${params.toString()}`);
            const response = await axios.get(`/api/users?${params.toString()}`);
            setUsers(response.data);
        } catch (error) {
            console.error('Error fetching users:', error);
        }
        setLoading(false);
    };

    console.log(users);

    const fetchRolesAndGroups = async () => {
        try {
            const [rolesResponse, groupsResponse, managersResponse] = await Promise.all([
                axios.get('/api/roles'),
                axios.get('/api/groups'),
                axios.get('/api/users-managers')
            ]);
            setRoles(rolesResponse.data.data || rolesResponse.data);
            setGroups(groupsResponse.data.data || groupsResponse.data);
            setManagers(managersResponse.data);
        } catch (error) {
            console.error('Error fetching roles, groups, and managers:', error);
        }
    };

    useEffect(() => {
        fetchRolesAndGroups();
    }, []);

    useEffect(() => {
        fetchUsers();
    }, [currentPage, perPage, searchTerm, selectedRole, selectedGroup]);

    const handleSearch = (value: string) => {
        setSearchTerm(value);
        setCurrentPage(1); // Reset to first page when searching
    };

    const handleRoleFilter = (value: string) => {
        setSelectedRole(value === 'all' ? '' : value);
        setCurrentPage(1);
    };

    const handleGroupFilter = (value: string) => {
        setSelectedGroup(value === 'all' ? '' : value);
        setCurrentPage(1);
    };

    const handleAddUser = () => {
        setEditingUser(null);
        setIsModalOpen(true);
    };

    const handleEditUser = (user: User) => {
        setEditingUser(user);
        setIsModalOpen(true);
    };

    const handleDeleteUser = async (userId: number) => {
        if (confirm('Are you sure you want to delete this user?')) {
            try {
                await axios.delete(`/api/users/${userId}`);
                fetchUsers(); // Refresh the list
            } catch (error) {
                console.error('Error deleting user:', error);
            }
        }
    };

    const handleModalClose = () => {
        setIsModalOpen(false);
        setEditingUser(null);
    };

    const handleUserSaved = () => {
        fetchUsers(); // Refresh the list
        handleModalClose();
    };

    const handleChangePassword = (user: User) => {
        setPasswordUser(user);
        setIsPasswordModalOpen(true);
    };

    const handlePasswordModalClose = () => {
        setIsPasswordModalOpen(false);
        setPasswordUser(null);
    };

    const handlePasswordChanged = () => {
        // No need to refresh the list for password changes
        handlePasswordModalClose();
    };

    const generatePaginationItems = () => {
        if (!users) return [];
        
        const items = [];
        const totalPages = users.last_page;
        const current = users.current_page;
        
        // Show up to 5 page numbers
        const start = Math.max(1, current - 2);
        const end = Math.min(totalPages, start + 4);
        
        for (let i = start; i <= end; i++) {
            items.push(i);
        }
        
        return items;
    };

    const getUserInitials = (name: string) => {
        return name
            .split(' ')
            .map(word => word.charAt(0))
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Users', href: '/users' }
            ]}
        >
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                <div className="flex items-center justify-between">
                    <Heading title="Users Management" />
                    <Button onClick={handleAddUser} className="flex items-center gap-2">
                        <Plus className="h-4 w-4" />
                        Add User
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>All Users</CardTitle>
                        <CardDescription>
                            Manage user accounts, roles, and groups in your organization.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {/* Filters */}
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                <Input
                                    placeholder="Search by name or email..."
                                    value={searchTerm}
                                    onChange={(e) => handleSearch(e.target.value)}
                                    className="pl-9"
                                />
                            </div>
                            <Select value={selectedRole || 'all'} onValueChange={handleRoleFilter}>
                                <SelectTrigger className="w-full sm:w-40">
                                    <SelectValue placeholder="Filter by role" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Roles</SelectItem>
                                    {roles.map((role) => (
                                        <SelectItem key={role.id} value={role.id.toString()}>
                                            {role.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select value={selectedGroup || 'all'} onValueChange={handleGroupFilter}>
                                <SelectTrigger className="w-full sm:w-40">
                                    <SelectValue placeholder="Filter by group" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Groups</SelectItem>
                                    {groups.map((group) => (
                                        <SelectItem key={group.id} value={group.id.toString()}>
                                            {group.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        {/* Loading State */}
                        {loading && (
                            <div className="flex items-center justify-center py-8">
                                <div className="text-muted-foreground">Loading users...</div>
                            </div>
                        )}

                        {/* Users Table */}
                        {!loading && users && (
                            <>
                                <div className="rounded-md border">
                                    <div className="overflow-x-auto">
                                        <table className="w-full">
                                            <thead className="border-b bg-muted/50">
                                                <tr>
                                                    <th className="p-3 text-left font-medium">User</th>
                                                    <th className="p-3 text-left font-medium">Contact</th>
                                                    <th className="p-3 text-left font-medium">Role</th>
                                                    <th className="p-3 text-left font-medium">Group</th>
                                                    <th className="p-3 text-left font-medium">Manager</th>
                                                    <th className="p-3 text-left font-medium">Designation</th>
                                                    <th className="p-3 text-left font-medium">Status</th>
                                                    <th className="p-3 text-right font-medium">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {users.data.map((user) => (
                                                    <tr key={user.id} className="border-b hover:bg-muted/25">
                                                        <td className="p-3">
                                                            <div className="flex items-center gap-3">
                                                                <Avatar className="h-8 w-8">
                                                                    <AvatarFallback className="text-xs">
                                                                        {getUserInitials(user.name)}
                                                                    </AvatarFallback>
                                                                </Avatar>
                                                                <div>
                                                                    <div className="font-medium">{user.name}</div>
                                                                    <div className="text-sm text-muted-foreground">ID: {user.id}</div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td className="p-3">
                                                            <div>
                                                                <div className="text-sm">{user.email}</div>
                                                                {user.phone && (
                                                                    <div className="text-sm text-muted-foreground">{user.phone}</div>
                                                                )}
                                                            </div>
                                                        </td>
                                                        <td className="p-3">
                                                            {user.role ? (
                                                                <Badge variant="secondary">{user.role.name}</Badge>
                                                            ) : (
                                                                <span className="text-muted-foreground">No role</span>
                                                            )}
                                                        </td>
                                                        <td className="p-3">
                                                            {user.group ? (
                                                                <Badge variant="outline">{user.group.name}</Badge>
                                                            ) : (
                                                                <span className="text-muted-foreground">No group</span>
                                                            )}
                                                        </td>
                                                        <td className="p-3">
                                                            {user.manager ? (
                                                                <span className="text-sm font-medium">{user.manager.name}</span>
                                                            ) : (
                                                                <span className="text-muted-foreground">No manager</span>
                                                            )}
                                                        </td>
                                                        <td className="p-3">
                                                            <span className="text-sm">
                                                                {user.designation || '-'}
                                                            </span>
                                                        </td>
                                                        <td className="p-3">
                                                            <Badge variant={user.email_verified_at ? "default" : "destructive"}>
                                                                {user.email_verified_at ? 'Verified' : 'Unverified'}
                                                            </Badge>
                                                        </td>
                                                        <td className="p-3">
                                                            <div className="flex items-center justify-end gap-2">
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => handleEditUser(user)}
                                                                    className="h-8 w-8 p-0"
                                                                    title="Edit User"
                                                                >
                                                                    <Edit2 className="h-4 w-4" />
                                                                </Button>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => handleChangePassword(user)}
                                                                    className="h-8 w-8 p-0 text-orange-600 hover:text-orange-700"
                                                                    title="Change Password"
                                                                >
                                                                    <Key className="h-4 w-4" />
                                                                </Button>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => handleDeleteUser(user.id)}
                                                                    className="h-8 w-8 p-0 text-destructive hover:text-destructive"
                                                                    title="Delete User"
                                                                >
                                                                    <Trash2 className="h-4 w-4" />
                                                                </Button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                {/* Pagination */}
                                {users.last_page > 1 && (
                                    <div className="flex items-center justify-between">
                                        <div className="text-sm text-muted-foreground">
                                            Showing {users.from} to {users.to} of {users.total} results
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => setCurrentPage(currentPage - 1)}
                                                disabled={users.current_page === 1}
                                            >
                                                Previous
                                            </Button>
                                            {generatePaginationItems().map((page) => (
                                                <Button
                                                    key={page}
                                                    variant={page === users.current_page ? "default" : "outline"}
                                                    size="sm"
                                                    onClick={() => setCurrentPage(page)}
                                                    className="h-8 w-8 p-0"
                                                >
                                                    {page}
                                                </Button>
                                            ))}
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => setCurrentPage(currentPage + 1)}
                                                disabled={users.current_page === users.last_page}
                                            >
                                                Next
                                            </Button>
                                        </div>
                                    </div>
                                )}

                                {/* Results count and per page selector */}
                                <div className="flex items-center justify-between border-t pt-4">
                                    <div className="flex items-center gap-2">
                                        <span className="text-sm text-muted-foreground">Per page:</span>
                                        <Select value={perPage.toString()} onValueChange={(value) => {
                                            setPerPage(parseInt(value));
                                            setCurrentPage(1);
                                        }}>
                                            <SelectTrigger className="w-20">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="5">5</SelectItem>
                                                <SelectItem value="10">10</SelectItem>
                                                <SelectItem value="25">25</SelectItem>
                                                <SelectItem value="50">50</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>
                            </>
                        )}

                        {/* Empty state */}
                        {!loading && users && users.data.length === 0 && (
                            <div className="flex flex-col items-center justify-center py-8">
                                <div className="text-muted-foreground">No users found</div>
                                <div className="text-sm text-muted-foreground">
                                    Try adjusting your search or filter criteria
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* User Modal */}
            <UserModal
                isOpen={isModalOpen}
                onClose={handleModalClose}
                onSave={handleUserSaved}
                user={editingUser}
                roles={roles}
                groups={groups}
                managers={managers}
            />

            {/* Password Change Modal */}
            <UserPasswordModal
                isOpen={isPasswordModalOpen}
                onClose={handlePasswordModalClose}
                onSuccess={handlePasswordChanged}
                user={passwordUser}
            />
        </AppLayout>
    );
}
