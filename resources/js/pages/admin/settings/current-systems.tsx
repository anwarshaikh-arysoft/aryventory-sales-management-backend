import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import DataTable from '@/components/data-table';
import CrudModal from '@/components/crud-modal';
import DeleteConfirmationModal from '@/components/delete-confirmation-modal';
import { CurrentSystem } from '@/types';
import { useState } from 'react';

const columns = [
    { key: 'id', label: 'ID', type: 'number' as const },
    { key: 'name', label: 'Name', type: 'text' as const },
    { key: 'created_at', label: 'Created', type: 'date' as const },
];

const formFields = [
    { 
        name: 'name', 
        label: 'Current System Name', 
        type: 'text' as const, 
        required: true, 
        placeholder: 'Enter current system name' 
    },
];

export default function CurrentSystemsPage() {
    const [showAddModal, setShowAddModal] = useState(false);
    const [showEditModal, setShowEditModal] = useState(false);
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [selectedItem, setSelectedItem] = useState<CurrentSystem | null>(null);
    const [refreshTrigger, setRefreshTrigger] = useState(0);

    const handleAdd = () => {
        setSelectedItem(null);
        setShowAddModal(true);
    };

    const handleEdit = (item: CurrentSystem) => {
        setSelectedItem(item);
        setShowEditModal(true);
    };

    const handleDelete = (item: CurrentSystem) => {
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
                    title="Current Systems"
                    description="Manage current systems for lead tracking"
                    apiEndpoint="/api/current-systems"
                    columns={columns}
                    onAdd={handleAdd}
                    onEdit={handleEdit}
                    onDelete={handleDelete}
                    searchPlaceholder="Search current systems..."
                    refreshTrigger={refreshTrigger}
                />

                <CrudModal
                    isOpen={showAddModal}
                    onClose={() => setShowAddModal(false)}
                    onSave={handleModalSave}
                    title="Current System"
                    description="Add a new current system"
                    apiEndpoint="/api/current-systems"
                    fields={formFields}
                    item={null}
                />

                <CrudModal
                    isOpen={showEditModal}
                    onClose={() => setShowEditModal(false)}
                    onSave={handleModalSave}
                    title="Current System"
                    description="Edit current system details"
                    apiEndpoint="/api/current-systems"
                    fields={formFields}
                    item={selectedItem}
                />

                <DeleteConfirmationModal
                    isOpen={showDeleteModal}
                    onClose={() => setShowDeleteModal(false)}
                    onDeleted={handleDeleteConfirm}
                    title="Current System"
                    apiEndpoint="/api/current-systems"
                    item={selectedItem}
                    itemDisplayName="name"
                />
            </SettingsLayout>
        </AppLayout>
    );
}
