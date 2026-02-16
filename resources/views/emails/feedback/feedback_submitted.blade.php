@extends('emails.notifications.layout')

@section('content')
    <div class="greeting">Hello Admin,</div>
    
    <div class="message">
        A new feedback has been submitted on Haat by 
        <strong>{{ $feedback->user->name ?? 'Guest' }}</strong> ({{ $feedback->user->email ?? 'No email' }}).
    </div>

    <div>
        <h3>Feedback Details:</h3>
        <ol>
            @foreach($feedback->answers as $answer)
                <li>
                    <strong>{{ $answer->question->question_text }}:</strong>
                    @if($answer->option_id)
                        {{ $answer->option->option_label }}
                    @endif
                    @if($answer->answer_text)
                        {{ $answer->answer_text }}
                    @endif
                </li>
            @endforeach
        </ol>
    </div>

    <div class="message">
        Submitted at: {{ $feedback->created_at->format('d M Y, H:i') }}
    </div>
@endsection
