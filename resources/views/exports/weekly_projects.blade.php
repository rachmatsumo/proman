<table>
    <thead>
        <tr>
            <th>No</th>
            <th>Program / Sub Program / Milestone / Activity</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Progress (%)</th>
            <th>Status</th>
            @foreach($weeks as $week)
                <th>{{ $week['label'] }}<br><small>{{ $week['start']->format('d/m/Y') }} - {{ $week['end']->format('d/m/Y') }}</small></th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        
        @foreach($programs as $program)
            <!-- Program Row -->
            <tr style="font-weight: bold; background-color: #e0e0e0;">
                <td>{{ $loop->iteration }}</td>
                <td>{{ $program->name }}</td>
                <td>{{ $program->start_date ? $program->start_date->format('d/m/Y') : '' }}</td>
                <td>{{ $program->end_date ? $program->end_date->format('d/m/Y') : '' }}</td>
                <td></td>
                <td></td>
                @foreach($weeks as $week)
                    <td></td>
                @endforeach
            </tr>
            
            @foreach($program->subPrograms as $subIndex => $sub)
                <!-- Sub Program Row -->
                <tr style="font-weight: bold; background-color: #f5f5f5;">
                    <td>{{ $loop->parent->iteration }}.{{ $subIndex + 1 }}</td>
                    <td style="padding-left: 20px;">{{ $sub->name }}</td>
                    <td>{{ $sub->start_date ? $sub->start_date->format('d/m/Y') : '' }}</td>
                    <td>{{ $sub->end_date ? $sub->end_date->format('d/m/Y') : '' }}</td>
                    <td></td>
                    <td></td>
                    @foreach($weeks as $week)
                        <td></td>
                    @endforeach
                </tr>
                
                @foreach($sub->milestones as $msIndex => $ms)
                    <!-- Milestone Row -->
                    <tr style="font-style: italic;">
                        <td>{{ $loop->parent->parent->iteration }}.{{ $subIndex + 1 }}.{{ $msIndex + 1 }}</td>
                        <td style="padding-left: 40px;">{{ $ms->name }}</td>
                        <td>{{ $ms->start_date ? $ms->start_date->format('d/m/Y') : '' }}</td>
                        <td>{{ $ms->end_date ? $ms->end_date->format('d/m/Y') : '' }}</td>
                        <td></td>
                        <td></td>
                        @foreach($weeks as $week)
                            <td></td>
                        @endforeach
                    </tr>
                    
                    @foreach($ms->activities as $actIndex => $act)
                        <!-- Activity Row -->
                        <tr>
                            <td></td>
                            <td style="padding-left: 60px;">- {{ $act->name }}</td>
                            <td>{{ $act->start_date->format('d/m/Y') }}</td>
                            <td>{{ $act->end_date->format('d/m/Y') }}</td>
                            <td>{{ $act->progress }}%</td>
                            <td>{{ ucfirst($act->status) }}</td>
                            
                            @foreach($weeks as $week)
                                @php
                                    // Check if activity intersects with this week
                                    $actStart = clone $act->start_date;
                                    $actEnd = clone $act->end_date;
                                    // To simplify intersection: if actStart <= weekEnd && actEnd >= weekStart
                                    $inWeek = ($actStart->lte($week['end']) && $actEnd->gte($week['start']));
                                @endphp
                                <td style="{{ $inWeek ? 'background-color: #3788d8; color: white; text-align: center;' : '' }}">
                                    {{ $inWeek ? 'X' : '' }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach
            @endforeach
        @endforeach
    </tbody>
</table>
