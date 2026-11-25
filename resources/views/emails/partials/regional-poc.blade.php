@if (!empty($regionalPocs))
    <p>If you require any assistance, please contact your designated HRPSP Regional HR Point of Contact (PoC) below.</p>
    @foreach ($regionalPocs as $poc)
        <div style="border: 1px solid #dce4f2; border-left: 4px solid #0A3AA9; border-radius: 8px; padding: 18px 20px; margin: 16px 0; background-color: #f7f9ff;">
            <p style="margin: 0 0 5px; color: #0A3AA9; font-size: 18px;"><strong>{{ $poc['name'] }}</strong></p>
            <p style="margin: 0 0 12px; color: #555;">{{ $poc['designation'] }}</p>
            <p style="margin: 0 0 6px;"><strong>Email:</strong> <a href="mailto:{{ $poc['email'] }}" style="color: #0A3AA9;">{{ $poc['email'] }}</a></p>
            <p style="margin: 0;"><strong>Mobile:</strong> <a href="tel:{{ $poc['mobile'] }}" style="color: #0A3AA9; text-decoration: none;">{{ $poc['mobile'] }}</a></p>
        </div>
    @endforeach
@else
    <p>If you require any assistance, please contact HRPSP HR at <a href="mailto:hr@hrpsp.net" style="color: #0A3AA9;">hr@hrpsp.net</a> for your designated Regional HR Point of Contact (PoC).</p>
@endif
