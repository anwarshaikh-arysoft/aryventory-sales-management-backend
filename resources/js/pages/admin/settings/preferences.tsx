import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import DataTable from '@/components/data-table';
import CrudModal from '@/components/crud-modal';
import DeleteConfirmationModal from '@/components/delete-confirmation-modal';
import { Preference } from '@/types';
import { useState } from 'react';

const columns = [
    { key: 'id', label: 'ID', type: 'number' as const },
    { key: 'name', label: 'Name', type: 'text' as const },
    { key: 'created_at', label: 'Created', type: 'date' as const },
];

const formFields = [
    { 
        name: 'name', 
        label: 'Preference Name', 
        type: 'text' as const, 
        required: true, 
        placeholder: 'Enter preference name' 
    },
];

export default function PreferencesPage() {
    const [showAddModal, setShowAddModal] = useState(false);
    const [showEditModal, setShowEditModal] = useState(false);
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [selectedItem, setSelectedItem] = useState<Preference | null>(null);
    const [refreshTrigger, setRefreshTrigger] = useState(0);

    const handleAdd = () => {
        setSelectedItem(null);
        setShowAddModal(true);
    };

    const handleEdit = (item: Preference) => {
        setSelectedItem(item);
        setShowEditModal(true);
    };

    const handleDelete = (item: Preference) => {
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
                    title="Preferences"
                    description="Manage user preferences and settings"
                    apiEndpoint="/api/preferences"
                    columns={columns}
                    onAdd={handleAdd}
                    onEdit={handleEdit}
                    onDelete={handleDelete}
                    searchPlaceholder="Search preferences..."
                    refreshTrigger={refreshTrigger}
                />

                <CrudModal
                    isOpen={showAddModal}
                    onClose={() => setShowAddModal(false)}
                    onSave={handleModalSave}
                    title="Preference"
                    description="Add a new preference"
                    apiEndpoint="/api/preferences"
                    fields={formFields}
                    item={null}
                />

                <CrudModal
                    isOpen={showEditModal}
                    onClose={() => setShowEditModal(false)}
                    onSave={handleModalSave}
                    title="Preference"
                    description="Edit preference details"
                    apiEndpoint="/api/preferences"
                    fields={formFields}
                    item={selectedItem}
                />

                <DeleteConfirmationModal
                    isOpen={showDeleteModal}
                    onClose={() => setShowDeleteModal(false)}
                    onDeleted={handleDeleteConfirm}
                    title="Preference"
                    apiEndpoint="/api/preferences"
                    item={selectedItem}
                    itemDisplayName="name"
                />
            </SettingsLayout>
        </AppLayout>
    );
}
