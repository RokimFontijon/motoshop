{{-- Original line drawing of a motorcycle (inline SVG, no image file needed). --}}
            <svg class="{{ $class ?? '' }}" viewBox="0 0 620 330" role="img" aria-label="Illustration of a motorcycle">
                <defs>
                    <linearGradient id="tank" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0" stop-color="#ff8a3d"/>
                        <stop offset="1" stop-color="#e8590c"/>
                    </linearGradient>
                    <radialGradient id="glow" cx="50%" cy="50%" r="50%">
                        <stop offset="0" stop-color="#ffd8a8" stop-opacity=".9"/>
                        <stop offset="1" stop-color="#ffd8a8" stop-opacity="0"/>
                    </radialGradient>
                </defs>

                {{-- road --}}
                <line x1="10" y1="296" x2="610" y2="296" stroke="#2f3b4c" stroke-width="3"/>
                <line x1="10" y1="312" x2="610" y2="312" stroke="#2f3b4c" stroke-width="2" stroke-dasharray="26 18"/>

                {{-- headlight beam --}}
                <ellipse cx="548" cy="156" rx="64" ry="30" fill="url(#glow)"/>

                {{-- wheels: tire, rim, spokes, hub --}}
                <g fill="none" stroke-linecap="round">
                    <g transform="translate(138 232)">
                        <circle r="62" stroke="#0e141b" stroke-width="14"/>
                        <circle r="55" stroke="#475569" stroke-width="2"/>
                        <circle r="38" stroke="#94a3b8" stroke-width="3"/>
                        <g stroke="#64748b" stroke-width="2">
                            <line x1="-38" y1="0" x2="38" y2="0"/><line x1="0" y1="-38" x2="0" y2="38"/>
                            <line x1="-27" y1="-27" x2="27" y2="27"/><line x1="-27" y1="27" x2="27" y2="-27"/>
                        </g>
                        <circle r="9" fill="#e8590c" stroke="none"/>
                    </g>
                    <g transform="translate(494 232)">
                        <circle r="62" stroke="#0e141b" stroke-width="14"/>
                        <circle r="55" stroke="#475569" stroke-width="2"/>
                        <circle r="38" stroke="#94a3b8" stroke-width="3"/>
                        <g stroke="#64748b" stroke-width="2">
                            <line x1="-38" y1="0" x2="38" y2="0"/><line x1="0" y1="-38" x2="0" y2="38"/>
                            <line x1="-27" y1="-27" x2="27" y2="27"/><line x1="-27" y1="27" x2="27" y2="-27"/>
                        </g>
                        <circle r="9" fill="#e8590c" stroke="none"/>
                    </g>

                    {{-- swing arm and frame --}}
                    <path d="M138 232 L262 222" stroke="#cbd5e1" stroke-width="8"/>
                    <path d="M262 222 L330 160 L430 150" stroke="#cbd5e1" stroke-width="8" stroke-linejoin="round"/>
                    <path d="M262 222 L336 224 L368 168" stroke="#94a3b8" stroke-width="6" stroke-linejoin="round"/>

                    {{-- front fork and handlebar --}}
                    <path d="M494 232 L452 128" stroke="#e2e8f0" stroke-width="9"/>
                    <path d="M452 128 L440 100 L402 96" stroke="#e2e8f0" stroke-width="7" stroke-linejoin="round"/>
                    <path d="M400 96 L388 100" stroke="#0e141b" stroke-width="12"/>

                    {{-- exhaust --}}
                    <path d="M300 238 Q250 262 196 258 L120 252" stroke="#94a3b8" stroke-width="9"/>
                    <path d="M196 262 L112 254" stroke="#64748b" stroke-width="13"/>
                </g>

                {{-- engine --}}
                <rect x="262" y="178" width="96" height="62" rx="12" fill="#1e293b" stroke="#64748b" stroke-width="3"/>
                <rect x="276" y="188" width="68" height="14" rx="4" fill="#334155"/>
                <rect x="276" y="208" width="68" height="14" rx="4" fill="#334155"/>
                <circle cx="318" cy="228" r="7" fill="#e8590c"/>

                {{-- fuel tank, seat, tail --}}
                <path d="M316 142 C340 112 396 104 438 120 L428 154 C392 142 350 146 322 168 Z" fill="url(#tank)"/>
                <path d="M338 128 C366 114 404 112 426 122" fill="none" stroke="#fff" stroke-opacity=".45" stroke-width="3" stroke-linecap="round"/>
                <path d="M186 168 C216 150 288 146 318 156 L322 172 C292 166 224 170 190 186 Z" fill="#0e141b" stroke="#475569" stroke-width="2"/>
                <path d="M164 176 L190 168 L196 188 L166 192 Z" fill="#e8590c"/>

                {{-- headlight and mirror --}}
                <circle cx="470" cy="144" r="17" fill="#fff4e0" stroke="#e8590c" stroke-width="4"/>
                <path d="M404 96 L396 72" stroke="#cbd5e1" stroke-width="4" stroke-linecap="round"/>
                <ellipse cx="394" cy="68" rx="9" ry="6" fill="#94a3b8"/>
            </svg>
