import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import EmployeeController from '@/actions/App/Http/Controllers/EmployeeController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SelectNative } from '@/components/ui/select-native';
import { dashboard } from '@/routes';
import { index } from '@/routes/employees';

type ClientOption = { id: number; name: string; unit_label: string | null };

type Employee = {
    id: number;
    client_id: number;
    employee_code: string | null;
    name: string;
    gender: 'male' | 'female' | null;
    uan: string | null;
    epf_member_id: string | null;
    esic_no: string | null;
    esic_applicable: boolean;
    esic_covered_from: string | null;
    skill_category: string;
    designation: string | null;
    rate_per_day: string;
    date_of_joining: string | null;
    status: string;
    bank_name: string | null;
    bank_account_no: string | null;
    bank_ifsc: string | null;
};

type DesignationOption = {
    designation: string;
    sac_code: string;
    sac_description: string | null;
};

export default function EmployeeForm({
    employee,
    clients,
    designations,
}: {
    employee?: Employee;
    clients: ClientOption[];
    designations: DesignationOption[];
}) {
    const [designation, setDesignation] = useState(employee?.designation ?? '');
    const selected = designations.find(
        (option) => option.designation.toLowerCase() === designation.toLowerCase(),
    );
    const isEdit = !!employee;
    const submitOptions = isEdit
        ? EmployeeController.update.form(employee!.id)
        : EmployeeController.store.form();

    return (
        <>
            <Head title={isEdit ? 'Edit Employee' : 'Add Employee'} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Heading
                    title={isEdit ? 'Edit Employee' : 'Add Employee'}
                    description="Deployment, statutory IDs and bank details"
                />

                <Form {...submitOptions} className="max-w-3xl space-y-6">
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-4 sm:grid-cols-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        defaultValue={employee?.name}
                                        required
                                    />
                                    <InputError message={errors.name} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="gender">Gender</Label>
                                    <SelectNative
                                        id="gender"
                                        name="gender"
                                        defaultValue={employee?.gender ?? ''}
                                    >
                                        <option value="">Select gender</option>
                                        <option value="male">Male (M)</option>
                                        <option value="female">Female (F)</option>
                                    </SelectNative>
                                    <InputError message={errors.gender} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="client_id">Client</Label>
                                    <SelectNative
                                        id="client_id"
                                        name="client_id"
                                        defaultValue={employee?.client_id ?? ''}
                                        required
                                    >
                                        <option value="" disabled>
                                            Select client
                                        </option>
                                        {clients.map((c) => (
                                            <option key={c.id} value={c.id}>
                                                {c.name}
                                                {c.unit_label
                                                    ? ` - ${c.unit_label}`
                                                    : ''}
                                            </option>
                                        ))}
                                    </SelectNative>
                                    <InputError message={errors.client_id} />
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="employee_code">
                                        Employee Code
                                    </Label>
                                    <Input
                                        id="employee_code"
                                        name="employee_code"
                                        defaultValue={
                                            employee?.employee_code ?? ''
                                        }
                                    />
                                    <InputError
                                        message={errors.employee_code}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="designation">
                                        Designation
                                    </Label>
                                    <SelectNative
                                        id="designation"
                                        name="designation"
                                        value={designation}
                                        onChange={(e) =>
                                            setDesignation(e.target.value)
                                        }
                                    >
                                        <option value="">Select designation</option>
                                        {employee?.designation &&
                                            !selected && (
                                                <option value={employee.designation}>
                                                    {employee.designation}
                                                </option>
                                            )}
                                        {designations.map((option) => (
                                            <option
                                                key={option.designation}
                                                value={option.designation}
                                            >
                                                {option.designation}
                                            </option>
                                        ))}
                                    </SelectNative>
                                    {selected && (
                                        <p className="text-muted-foreground text-xs">
                                            SAC {selected.sac_code}
                                            {selected.sac_description
                                                ? ` · ${selected.sac_description}`
                                                : ''}
                                        </p>
                                    )}
                                    <p className="text-muted-foreground text-xs">
                                        Not in the list?{' '}
                                        <Link
                                            href="/settings/sac-codes"
                                            className="underline"
                                        >
                                            Add it under Settings, SAC codes
                                        </Link>
                                        .
                                    </p>
                                    <InputError message={errors.designation} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="skill_category">
                                        Skill Category
                                    </Label>
                                    <SelectNative
                                        id="skill_category"
                                        name="skill_category"
                                        defaultValue={
                                            employee?.skill_category ?? ''
                                        }
                                        required
                                    >
                                        <option value="" disabled>
                                            Select Skill Category
                                        </option>
                                        <option value="high_skilled">
                                            High-Skilled
                                        </option>
                                        <option value="skilled">Skilled</option>
                                        <option value="semi_skilled">
                                            Semi-Skilled
                                        </option>
                                        <option value="unskilled">
                                            Unskilled
                                        </option>
                                    </SelectNative>
                                    <InputError
                                        message={errors.skill_category}
                                    />
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="rate_per_day">
                                        Rate per Day (Rs.)
                                    </Label>
                                    <Input
                                        id="rate_per_day"
                                        name="rate_per_day"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        defaultValue={employee?.rate_per_day}
                                        required
                                    />
                                    <InputError message={errors.rate_per_day} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="date_of_joining">
                                        Date of Joining
                                    </Label>
                                    <Input
                                        id="date_of_joining"
                                        name="date_of_joining"
                                        type="date"
                                        defaultValue={
                                            employee?.date_of_joining ?? ''
                                        }
                                    />
                                    <InputError
                                        message={errors.date_of_joining}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="status">Status</Label>
                                    <SelectNative
                                        id="status"
                                        name="status"
                                        defaultValue={
                                            employee?.status ?? 'active'
                                        }
                                        required
                                    >
                                        <option value="active">Active</option>
                                        <option value="inactive">
                                            Inactive
                                        </option>
                                        <option value="left">Left</option>
                                    </SelectNative>
                                    <InputError message={errors.status} />
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="uan">EPFO UAN</Label>
                                    <Input
                                        id="uan"
                                        name="uan"
                                        placeholder="Universal Account Number"
                                        defaultValue={employee?.uan ?? ''}
                                    />
                                    <InputError message={errors.uan} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="epf_member_id">
                                        EPF Member ID
                                    </Label>
                                    <Input
                                        id="epf_member_id"
                                        name="epf_member_id"
                                        defaultValue={
                                            employee?.epf_member_id ?? ''
                                        }
                                    />
                                    <InputError
                                        message={errors.epf_member_id}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="esic_no">
                                        ESIC IP Number
                                    </Label>
                                    <Input
                                        id="esic_no"
                                        name="esic_no"
                                        placeholder="Insured Person number"
                                        defaultValue={employee?.esic_no ?? ''}
                                    />
                                    <InputError message={errors.esic_no} />
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-3">
                                <div className="grid gap-2">
                                    <input type="hidden" name="esic_applicable_form" value="1" />
                                    <div className="flex items-center gap-2">
                                        <Checkbox
                                            id="esic_applicable"
                                            name="esic_applicable"
                                            defaultChecked={employee?.esic_applicable ?? true}
                                        />
                                        <Label htmlFor="esic_applicable">
                                            ESIC applicable
                                        </Label>
                                    </div>
                                    <p className="text-muted-foreground text-xs">
                                        Untick for an employee who is not to be covered under ESIC.
                                    </p>
                                    <InputError message={errors.esic_applicable} />
                                </div>
                                <div className="grid gap-2 sm:col-span-2">
                                    <Label htmlFor="esic_covered_from">
                                        Covered under ESIC since (optional)
                                    </Label>
                                    <Input
                                        id="esic_covered_from"
                                        name="esic_covered_from"
                                        type="date"
                                        className="sm:max-w-xs"
                                        defaultValue={employee?.esic_covered_from ?? ''}
                                    />
                                    <p className="text-muted-foreground text-xs">
                                        Fill this if the employee was already covered during the current contribution period (April to September, or October to March) and that is not visible from saved wages. Coverage then continues to the end of that period even if wages cross the ceiling.
                                    </p>
                                    <InputError message={errors.esic_covered_from} />
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="bank_name">Bank Name</Label>
                                    <Input
                                        id="bank_name"
                                        name="bank_name"
                                        defaultValue={employee?.bank_name ?? ''}
                                    />
                                    <InputError message={errors.bank_name} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="bank_account_no">
                                        Account No.
                                    </Label>
                                    <Input
                                        id="bank_account_no"
                                        name="bank_account_no"
                                        defaultValue={
                                            employee?.bank_account_no ?? ''
                                        }
                                    />
                                    <InputError
                                        message={errors.bank_account_no}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="bank_ifsc">IFSC</Label>
                                    <Input
                                        id="bank_ifsc"
                                        name="bank_ifsc"
                                        defaultValue={employee?.bank_ifsc ?? ''}
                                    />
                                    <InputError message={errors.bank_ifsc} />
                                </div>
                            </div>

                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing}>
                                    {isEdit
                                        ? 'Save Changes'
                                        : 'Create Employee'}
                                </Button>
                                <Button asChild variant="outline">
                                    <a href={index().url}>Cancel</a>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

EmployeeForm.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Employees', href: index() },
        { title: 'Employee Form', href: index() },
    ],
};
