import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import DataTable from '@/components/data-table';
import CrudModal from '@/components/crud-modal';
import DeleteConfirmationModal from '@/components/delete-confirmation-modal';
import { Role } from '@/types';
import { useState } from 'react';

const columns = [
    { key: 'id', label: 'ID', type: 'number' as const },
    { key: 'name', label: 'Name', type: 'text' as const },
    { key: 'created_at', label: 'Created', type: 'date' as const },
];

const formFields = [
    { 
        name: 'name', 
        label: 'Role Name', 
        type: 'text' as const, 
        required: true, 
        placeholder: 'Enter role name' 
    },
];

export default function RolesPage() {
    const [showAddModal, setShowAddModal] = useState(false);
    const [showEditModal, setShowEditModal] = useState(false);
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [selectedItem, setSelectedItem] = useState<Role | null>(null);
    const [refreshTrigger, setRefreshTrigger] = useState(0);

    const handleAdd = () => {
        setSelectedItem(null);
        setShowAddModal(true);
    };

    const handleEdit = (item: Role) => {
        setSelectedItem(item);
        setShowEditModal(true);
    };

    const handleDelete = (item: Role) => {
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
                    title="Roles"
                    description="Manage user roles and permissions"
                    apiEndpoint="/api/roles"
                    columns={columns}
                    onAdd={handleAdd}
                    onEdit={handleEdit}
                    onDelete={handleDelete}
                    searchPlaceholder="Search roles..."
                    refreshTrigger={refreshTrigger}
                />

                <CrudModal
                    isOpen={showAddModal}
                    onClose={() => setShowAddModal(false)}
                    onSave={handleModalSave}
                    title="Role"
                    description="Add a new role"
                    apiEndpoint="/api/roles"
                    fields={formFields}
                    item={null}
                />

                <CrudModal
                    isOpen={showEditModal}
                    onClose={() => setShowEditModal(false)}
                    onSave={handleModalSave}
                    title="Role"
                    description="Edit role details"
                    apiEndpoint="/api/roles"
                    fields={formFields}
                    item={selectedItem}
                />

                <DeleteConfirmationModal
                    isOpen={showDeleteModal}
                    onClose={() => setShowDeleteModal(false)}
                    onDeleted={handleDeleteConfirm}
                    title="Role"
                    apiEndpoint="/api/roles"
                    item={selectedItem}
                    itemDisplayName="name"
                />
            </SettingsLayout>
        </AppLayout>
    );
}
