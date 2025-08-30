import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import DataTable from '@/components/data-table';
import CrudModal from '@/components/crud-modal';
import DeleteConfirmationModal from '@/components/delete-confirmation-modal';
import { Target, User } from '@/types';
import { useState, useEffect } from 'react';
import axios from 'axios';

const columns = [
    { key: 'id', label: 'ID', type: 'number' as const },
    { 
        key: 'user.name', 
        label: 'User', 
        type: 'text' as const,
        render: (value: any, item: Target) => item.user?.name || 'Unknown User'
    },
    { key: 'daily_meeting_targets', label: 'Daily Meetings', type: 'number' as const },
    { key: 'closure_target', label: 'Closure Target', type: 'number' as const },
    { key: 'revenue_targets', label: 'Revenue Target', type: 'currency' as const },
    { key: 'created_at', label: 'Created', type: 'date' as const },
];

export default function TargetsPage() {
    const [showAddModal, setShowAddModal] = useState(false);
    const [showEditModal, setShowEditModal] = useState(false);
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [selectedItem, setSelectedItem] = useState<Target | null>(null);
    const [refreshTrigger, setRefreshTrigger] = useState(0);
    const [users, setUsers] = useState<User[]>([]);

    // Fetch users for the dropdown
    useEffect(() => {
        const fetchUsers = async () => {
            try {
                const response = await axios.get('/api/users');
                setUsers(Array.isArray(response.data) ? response.data : response.data.data || []);
            } catch (error) {
                console.error('Error fetching users:', error);
                setUsers([]);
            }
        };
        fetchUsers();
    }, []);

    const formFields = [
        { 
            name: 'user_id', 
            label: 'User', 
            type: 'select' as const, 
            required: true,
            options: users.map(user => ({ value: user.id, label: user.name }))
        },
        { 
            name: 'daily_meeting_targets', 
            label: 'Daily Meeting Targets', 
            type: 'number' as const, 
            required: true, 
            placeholder: 'Enter daily meeting targets' 
        },
        { 
            name: 'closure_target', 
            label: 'Closure Target', 
            type: 'number' as const, 
            required: true, 
            placeholder: 'Enter closure target' 
        },
        { 
            name: 'revenue_targets', 
            label: 'Revenue Targets', 
            type: 'number' as const, 
            required: true, 
            placeholder: 'Enter revenue targets' 
        },
    ];

    const handleAdd = () => {
        setSelectedItem(null);
        setShowAddModal(true);
    };

    const handleEdit = (item: Target) => {
        setSelectedItem(item);
        setShowEditModal(true);
    };

    const handleDelete = (item: Target) => {
        setSelectedItem(item);
        setShowDeleteModal(true);
    };

    const handleModalSave = () => {
        setRefreshTrigger(prev => prev + 1);
        setShowAddModal(false);
        setShowEditModal(false);
    };

    const handleDeleteConfirm = () => {
        setRefreshTrigger(prev => prev + 1);
        setShowDeleteModal(false);
    };

    return (
        <AppLayout>
            <SettingsLayout>
                <DataTable
                    title="Targets"
                    description="Manage user targets and performance goals"
                    apiEndpoint="/api/targets"
                    columns={columns}
                    onAdd={handleAdd}
                    onEdit={handleEdit}
                    onDelete={handleDelete}
                    searchPlaceholder="Search targets..."
                    refreshTrigger={refreshTrigger}
                />

                <CrudModal
                    isOpen={showAddModal}
                    onClose={() => setShowAddModal(false)}
                    onSave={handleModalSave}
                    title="Target"
                    description="Add a new target"
                    apiEndpoint="/api/targets"
                    fields={formFields}
                    item={null}
                />

                <CrudModal
                    isOpen={showEditModal}
                    onClose={() => setShowEditModal(false)}
                    onSave={handleModalSave}
                    title="Target"
                    description="Edit target details"
                    apiEndpoint="/api/targets"
                    fields={formFields}
                    item={selectedItem}
                />

                <DeleteConfirmationModal
                    isOpen={showDeleteModal}
                    onClose={() => setShowDeleteModal(false)}
                    onDeleted={handleDeleteConfirm}
                    title="Target"
                    apiEndpoint="/api/targets"
                    item={selectedItem}
                    itemDisplayName="user.name"
                />
            </SettingsLayout>
        </AppLayout>
    );
}
