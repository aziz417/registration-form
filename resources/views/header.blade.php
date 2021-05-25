<nav class="navbar sticky-top navbar-light bg-dark">
        <div class="top-search-bar float-right">
            <form class="pdfRequestSend updateAndPdf" action="{{ route('search.register') }}" method="get">
                @csrf
                <div class="input-group">
                    @if(isset($register))
                        <div class="input-group-append">
                            <a href="{{ route('registration.create') }}" class="btn btn-success">Back</a>
                        </div>
                    @endif
                    <input id="searchKey" onkeyup="getSuggestion(this)"  type="number" name="key" class="form-control" placeholder="Search by phone / NID / Registration" required>
                    <div class="input-group-append">
                        <button class="btn btn-warning" type="submit">Search</button>
                        <button class="btn btn-success" formtarget="_blank" id="pdfGenerate">PDF Generate</button>
                    </div>
                </div>
            </form>
        </div>

    @auth
        @if(Auth::user()->role === 'admin')
            <a class="m-1" href="{{ route('admin') }}">
                Dashboard
            </a>
        @endif

        <a class="m-1" href="{{ route('logout') }}"
                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            <i class="fa fa-sign-out"></i> Logout
        </a>

        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
    @endauth
</nav>

<style>
    .auto-suggestion-section{
        width: 100%;
        float: left;
        position: relative;
    }

    .autocomplete-result{
        width: 70%;
        margin: 0 auto;
        position: fixed;
        z-index: 99;
        top: 71px;
        left: 191px;
    }

    .autocomplete-result ul li{
        list-style: none;
    }

</style>

<div class="auto-suggestion-section">
    <div id="show-suggestion" class="autocomplete-result">

    </div>
</div>