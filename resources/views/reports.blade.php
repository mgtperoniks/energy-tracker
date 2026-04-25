@extends('layouts.app')

@section('content')
<main class="flex-1 transition-all duration-300 pt-16 md:pl-64">
    <div class="p-6 lg:p-10 max-w-7xl mx-auto">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8 no-print">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="inline-block w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                    <span class="text-label-sm uppercase tracking-widest text-outline font-bold text-[10px]">Energy Reporting Engine</span>
                </div>
                <h1 class="text-3xl font-extrabold tracking-tight text-on-surface">Consumption Reports</h1>
                <p class="text-on-surface-variant text-sm mt-1">Generate and export historical energy usage data</p>
            </div>
            
            @if($isGenerated && $reports->isNotEmpty())
            <div class="flex items-center gap-3">
                <button onclick="exportToCSV()" class="px-4 py-2 bg-emerald-600 text-white font-bold rounded-lg hover:bg-emerald-700 transition-all flex items-center gap-2 shadow-lg shadow-emerald-900/20">
                    <span class="material-symbols-outlined text-sm">grid_on</span>
                    <span>Excel</span>
                </button>
                <button onclick="window.print()" class="px-4 py-2 bg-rose-600 text-white font-bold rounded-lg hover:bg-rose-700 transition-all flex items-center gap-2 shadow-lg shadow-rose-900/20">
                    <span class="material-symbols-outlined text-sm">picture_as_pdf</span>
                    <span>PDF</span>
                </button>
            </div>
            @endif
        </div>

        <!-- Filter Card -->
        <div class="bg-surface-container-lowest rounded-2xl p-6 mb-8 shadow-sm border border-outline-variant/30 no-print">
            <form action="{{ route('reports') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
                <div>
                    <label class="block text-[10px] font-bold text-outline uppercase tracking-widest mb-2">Power Meter</label>
                    <select name="machine_id" class="w-full bg-surface-container-high border-none rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20">
                        <option value="">All Power Meters</option>
                        @foreach($machines as $machine)
                            <option value="{{ $machine->id }}" @selected($machineId == $machine->id)>
                                {{ $machine->code }} - {{ $machine->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-outline uppercase tracking-widest mb-2">Start Date</label>
                    <input type="date" name="start_date" value="{{ $startDate }}" class="w-full bg-surface-container-high border-none rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20" required>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-outline uppercase tracking-widest mb-2">End Date</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="w-full bg-surface-container-high border-none rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20" required>
                </div>
                <button type="submit" class="bg-primary text-white font-bold py-2.5 rounded-lg hover:bg-primary/90 transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">refresh</span>
                    <span>Generate report</span>
                </button>
            </form>
        </div>

        @if($isGenerated)
        <!-- Report Content -->
        <div class="bg-surface-container-lowest rounded-2xl overflow-hidden shadow-sm border border-outline-variant/30 print:shadow-none print:border-none">
            <!-- Professional Print-only Header -->
            <div class="hidden print:block p-8 border-b-2 border-primary mb-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-2xl font-black text-primary tracking-tighter uppercase">Energy Usage Report</h1>
                        <p class="text-xs font-bold text-on-surface-variant mt-1">PERoni Karya Sentra Industrial System</p>
                    </div>
                    <div class="text-right text-[10px] font-bold text-on-surface-variant uppercase tracking-widest">
                        <p>Generated: {{ now()->format('d M Y H:i') }}</p>
                        <p>Status: Official Record</p>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-8 mt-8 py-6 border-t border-b border-outline-variant/10">
                    <div>
                        <span class="text-[9px] font-black uppercase tracking-widest text-outline">Report Period</span>
                        <p class="text-sm font-bold mt-1 text-on-surface">{{ $startDate }} → {{ $endDate }}</p>
                    </div>
                    <div>
                        <span class="text-[9px] font-black uppercase tracking-widest text-outline">Target Asset</span>
                        <p class="text-sm font-bold mt-1 text-on-surface">
                            @if($machineId)
                                @php $selectedMachine = $machines->find($machineId); @endphp
                                {{ $selectedMachine->name }} ({{ $selectedMachine->code }})
                            @else
                                All Power Meters
                            @endif
                        </p>
                    </div>
                    <div>
                        <span class="text-[9px] font-black uppercase tracking-widest text-outline">Total Consumption</span>
                        <p class="text-sm font-black mt-1 text-primary">{{ number_format($reports->sum('kwh_usage'), 2) }} kWh</p>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto print:overflow-visible">
                <table id="report-table" class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low/50 print:bg-slate-100 text-on-surface-variant uppercase text-[9px] font-black tracking-widest border-b border-outline-variant/30">
                            <th class="px-6 py-4">Date</th>
                            <th class="px-6 py-4">Power Meter</th>
                            <th class="px-6 py-4">Area / Department</th>
                            <th class="px-6 py-4 text-right">Consumption</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        @forelse($reports as $row)
                        <tr class="hover:bg-surface-container-low/30 transition-colors print:break-inside-avoid">
                            <td class="px-6 py-3 text-xs font-bold font-mono text-on-surface">{{ $row->date }}</td>
                            <td class="px-6 py-3">
                                <div class="text-xs font-black text-primary">{{ $row->machine->code }}</div>
                            </td>
                            <td class="px-6 py-3 text-xs text-on-surface-variant font-medium">{{ $row->machine->name }}</td>
                            <td class="px-6 py-3 text-right">
                                <span class="font-mono text-xs font-black text-on-surface">{{ number_format($row->kwh_usage, 2) }}</span>
                                <span class="text-[9px] font-bold text-outline ml-0.5">kWh</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-on-surface-variant italic text-xs">
                                No records found for this period.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($reports->isNotEmpty())
                    <tfoot class="bg-surface-container-low/50 print:bg-slate-50 border-t-2 border-primary/20">
                        <tr>
                            <td colspan="3" class="px-6 py-4 font-black text-[10px] text-right uppercase tracking-widest">Grand Total Usage</td>
                            <td class="px-6 py-4 text-right font-mono font-black text-primary text-sm">
                                {{ number_format($reports->sum('kwh_usage'), 2) }} <span class="text-[9px]">kWh</span>
                            </td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
            
            <!-- Signature Block (Print Only) -->
            <div class="hidden print:grid grid-cols-2 gap-20 mt-16 px-10">
                <div class="text-center">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-outline mb-16">Prepared By</p>
                    <div class="border-b border-on-surface w-40 mx-auto"></div>
                    <p class="text-[10px] font-bold mt-2">Operator/Engineering</p>
                </div>
                <div class="text-center">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-outline mb-16">Authorized By</p>
                    <div class="border-b border-on-surface w-40 mx-auto"></div>
                    <p class="text-[10px] font-bold mt-2">Plant Manager</p>
                </div>
            </div>

            @if(method_exists($reports, 'hasPages') && $reports->hasPages())
            <div class="px-6 py-4 border-t border-outline-variant/10 no-print">
                {{ $reports->appends(request()->query())->links() }}
            </div>
            @endif
        </div>
        @else
        <!-- Welcome / Instructions Card -->
        <div class="bg-surface-container-lowest rounded-3xl p-12 text-center border-2 border-dashed border-outline-variant/50 flex flex-col items-center gap-6">
            <div class="w-20 h-20 rounded-full bg-primary/10 flex items-center justify-center">
                <span class="material-symbols-outlined text-4xl text-primary" style="font-variation-settings: 'FILL' 1;">assessment</span>
            </div>
            <div>
                <h3 class="text-xl font-bold text-on-surface">Ready to generate a report?</h3>
                <p class="text-on-surface-variant max-w-md mx-auto mt-2 leading-relaxed">
                    Select a **Power Meter** and a **Date Range** from the filter section above, then click the **"Generate"** button to view and download your consumption analysis.
                </p>
            </div>
            <div class="flex gap-4 text-left text-xs text-outline font-medium">
                <div class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-secondary"></span> Pilih Alat Ukur</div>
                <div class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-secondary"></span> Pilih Rentang Waktu</div>
                <div class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-secondary"></span> Klik Generate</div>
            </div>
        </div>
        @endif
    </div>
    </div>
</main>

<style>
    @media print {
        @page {
            size: A4;
            margin: 15mm;
        }
        header, aside, .no-print, nav, .fixed {
            display: none !important;
        }
        main {
            margin: 0 !important;
            padding: 0 !important;
        }
        .print\:shadow-none { box-shadow: none !important; }
        .print\:border-none { border: none !important; }
        body {
            background: white !important;
            color: black !important;
        }
    }
</style>

<script>
function exportToCSV() {
    const table = document.getElementById('report-table');
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].querySelectorAll('td, th');
        for (let j = 0; j < cols.length; j++) {
            // Clean up text and handle commas
            let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, '').replace(/(\s\s+)/gm, ' ');
            data = data.replace(/"/g, '""');
            row.push('"' + data + '"');
        }
        csv.push(row.join(','));
    }
    
    const csvContent = "data:text/csv;charset=utf-8," + csv.join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "energy_report_{{ date('Ymd_His') }}.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>
@endsection
