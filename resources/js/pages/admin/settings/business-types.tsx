import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import DataTable from '@/components/data-table';
import CrudModal from '@/components/crud-modal';
import DeleteConfirmationModal from '@/components/delete-confirmation-modal';
import { BusinessType } from '@/types';
import { useState } from 'react';

const columns = [
    { key: 'id', label: 'ID', type: 'number' as const },
    { key: 'name', label: 'Name', type: 'text' as const },
    { key: 'created_at', label: 'Created', type: 'date' as const },
];

const formFields = [
    { 
        name: 'name', 
        label: 'Business Type Name', 
        type: 'text' as const, 
        required: true, 
        placeholder: 'Enter business type name' 
    },
];

export default function BusinessTypesPage() {
    const [showAddModal, setShowAddModal] = useState(false);
    const [showEditModal, setShowEditModal] = useState(false);
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [selectedItem, setSelectedItem] = useState<BusinessType | null>(null);
    const [refreshTrigger, setRefreshTrigger] = useState(0);

    const handleAdd = () => {
        setSelectedItem(null);
        setShowAddModal(true);
    };

    const handleEdit = (item: BusinessType) => {
        setSelectedItem(item);
        setShowEditModal(true);
    };

    const handleDelete = (item: BusinessType) => {
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
                    title="Business Types"
                    description="Manage business types for lead categorization"
                    apiEndpoint="/api/business-types"
                    columns={columns}
                    onAdd={handleAdd}
                    onEdit={handleEdit}
                    onDelete={handleDelete}
                    searchPlaceholder="Search business types..."
                    refreshTrigger={refreshTrigger}
                />

                <CrudModal
                    isOpen={showAddModal}
                    onClose={() => setShowAddModal(false)}
                    onSave={handleModalSave}
                    title="Business Type"
                    description="Add a new business type"
                    apiEndpoint="/api/business-types"
                    fields={formFields}
                    item={null}
                />

                <CrudModal
                    isOpen={showEditModal}
                    onClose={() => setShowEditModal(false)}
                    onSave={handleModalSave}
                    title="Business Type"
                    description="Edit business type details"
                    apiEndpoint="/api/business-types"
                    fields={formFields}
                    item={selectedItem}
                />

                <DeleteConfirmationModal
                    isOpen={showDeleteModal}
                    onClose={() => setShowDeleteModal(false)}
                    onDeleted={handleDeleteConfirm}
                    title="Business Type"
                    apiEndpoint="/api/business-types"
                    item={selectedItem}
                    itemDisplayName="name"
                />
            </SettingsLayout>
        </AppLayout>
    );
}
