
<ul class="flex flex-row justify-center pb-3 -ml-4 -mr-4 text-sm font-bold">
    @if (config('wpbox.pp_url','#')!="#")
        <li> <a href="{{ config('wpbox.pp_url','#') }}" class="px-2 text-gray-500 hover:text-gray-600">{{ __('Privacy Policy')}}</a> </li>
    @endif
    @if (config('wpbox.disclaimer_url','#')!="#")
        <li> <a href="{{ config('wpbox.disclaimer_url','#') }}" class="px-2 text-gray-500 hover:text-gray-600">{{ __('Disclaimer')}}</a> </li>
    @endif
    @if (config('wpbox.terms_url','#')!="#")
        <li> <a href="{{ config('wpbox.terms_url','#') }}" class="px-2 text-gray-500 hover:text-gray-600">{{ __('Terms')}}</a> </li>
    @endif
</ul>
