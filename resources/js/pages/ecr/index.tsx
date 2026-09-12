import { Head } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { MONTHS } from '@/components/filter-bar';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { SelectNative } from '@/components/ui/select-native';
import { dashboard } from '@/routes';
import { download, index } from '@/routes/ecr';

export default function EcrIndex({
    month,
    year,
}: {
    month: number;
    year: number;
}) {
    const [selMonth, setSelMonth] = useState(String(month));
    const [selYear, setSelYear] = useState(String(year));
    const years = Array.from(
        { length: 6 },
        (_, i) => new Date().getFullYear() - 3 + i,
    );

    const downloadUrl = `${download().url}?${new URLSearchParams({ month: selMonth, year: selYear }).toString()}`;

    return (
        <>
            <Head title="ECR Export" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Heading
                    title="ECR Export"
                    description="Generate the EPFO Electronic Challan-cum-Return text file for a month"
                />

                <Card className="max-w-lg">
                    <CardHeader>
                        <CardTitle className="text-sm">Select Period</CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-wrap items-end gap-2">
                        <SelectNative
                            value={selMonth}
                            onChange={(e) => setSelMonth(e.target.value)}
                            className="w-36"
                        >
                            {MONTHS.map((m, i) => (
                                <option key={m} value={i + 1}>
                                    {m}
                                </option>
                            ))}
                        </SelectNative>
                        <SelectNative
                            value={selYear}
                            onChange={(e) => setSelYear(e.target.value)}
                            className="w-24"
                        >
                            {years.map((y) => (
                                <option key={y} value={y}>
                                    {y}
                                </option>
                            ))}
                        </SelectNative>
                        <Button asChild>
                            <a href={downloadUrl}>
                                <Download /> Generate &amp; Download ECR
                            </a>
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

EcrIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'ECR Export', href: index() },
    ],
};
