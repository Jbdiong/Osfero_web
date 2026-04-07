<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 overflow-hidden">
        <!-- Backdrop -->
        <div class="absolute  inset-0 " style="background-color: rgba(0, 0, 0, 0.5);" @click="close"></div>
        
        <!-- Panel -->
        <div class="absolute right-0 top-0 h-full w-[30vw] bg-white shadow-xl overflow-y-auto">
            <!-- Header -->
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between z-10">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <h2 class="text-xl font-semibold text-gray-900">New leads</h2>
                </div>
                <button @click="close" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Form -->
            <form @submit.prevent="submitForm" class="p-6 space-y-6">
                <!-- Basic Information -->
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <label class="text-sm font-medium text-gray-700">Basic information</label>
                    </div>
                    <div class=" grid grid-cols-2 gap-2">
                        <input
                            v-model="form.pic_name"
                            type="text"
                            placeholder="P.I.C Name"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 col-span-1"
                        />
                        <input
                            v-model="form.Shop_Name"
                            type="text"
                            placeholder="Company name"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 col-span-1"
                        />
                        <div class="relative col-span-2">
                            <svg class="absolute left-3 top-3 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                            <input
                                v-model="form.phone_number"
                                type="tel"
                                placeholder="Add a phone number"
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                    </div>
                </div>

                <!-- Location -->
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <label class="text-sm font-medium text-gray-700">Location</label>
                    </div>
                    <div class=" grid grid-cols-2 gap-2">
                         <!-- City Selection -->
                         <select
                            v-model="form.city_id"
                            @change="onCityChange"
                            class="col-span-2 w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 appearance-none bg-white"
                        >
                            <option value="">Select a city (optional)</option>
                            <option v-for="city in cities" :key="city.id" :value="city.id">
                                {{ city.name }}
                            </option>
                        </select>
                        
                        
                        <!-- State Selection -->
                        <select
                            v-model="form.state_id"
                            @change="onStateChange"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 appearance-none bg-white"
                        >
                            <option value="">Select a state (optional)</option>
                            <option v-for="state in states" :key="state.id" :value="state.id">
                                {{ state.name }}
                            </option>
                        </select>

                        <!-- Country Selection -->
                        <select
                            v-model="form.country_id"
                            @change="onCountryChange"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 appearance-none bg-white"
                        >
                            <option value="">Select a country (optional)</option>
                            <option v-for="country in countries" :key="country.id" :value="country.id">
                                {{ country.name }}
                            </option>
                        </select>
                        
                       
                        
                        
                    </div>
                </div>

                <!-- Industry and Marketer -->
                <div class="grid grid-cols-2 gap-2">
                    <div class="">
                    <div class="flex items-center gap-2 mb-4">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        <label class="text-sm font-medium text-gray-700">Industry</label>
                    </div>
                    <select
                        v-model="form.industry_id"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 appearance-none bg-white"
                    >
                        <option value="">Select an industry</option>
                        <option v-for="industry in industries" :key="industry.id" :value="industry.id">
                            {{ industry.label }}
                        </option>
                    </select>
                    </div>
                    <div class="">
                    <div class="flex items-center gap-2 mb-4">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <label class="text-sm font-medium text-gray-700">Marketer</label>
                    </div>
                    <select
                        v-model="form.marketer_id"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 appearance-none bg-white"
                    >
                        <option value="">Select a marketer</option>
                        <option v-for="marketer in marketers" :key="marketer.id" :value="marketer.id">
                            {{ marketer.name }}
                        </option>
                    </select>
                    </div>
                </div>



                <!-- Source -->
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                        </svg>
                        <label class="text-sm font-medium text-gray-700">Source</label>
                    </div>
                    <div class="flex gap-2 flex-wrap">
                        <button
                            v-for="source in sources"
                            :key="source.id"
                            type="button"
                            @click="form.source_id = source.id; form.Manual_Source = source.label === 'xhs' ? '小红书' : source.label"
                            :class="[
                                'px-4 py-2 rounded-md text-sm font-medium transition-colors',
                                form.source_id === source.id
                                    ? 'bg-red-500 text-white'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                            ]"
                        >
                            {{ source.label === 'xhs' ? '小红书' : source.label }}
                        </button>
                    </div>
                </div>

                <!-- Language -->
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                        </svg>
                        <label class="text-sm font-medium text-gray-700">Language</label>
                    </div>
                    <div class="flex gap-2 flex-wrap">
                        <button
                            v-for="language in languages"
                            :key="language.id"
                            type="button"
                            @click="form.language_id = language.id; form.Manual_Language = language.label"
                            :class="[
                                'px-4 py-2 rounded-md text-sm font-medium transition-colors',
                                form.language_id === language.id
                                    ? 'bg-orange-500 text-white'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                            ]"
                        >
                            {{ language.label }}
                        </button>
                    </div>
                </div>

                <!-- Remarks -->
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <label class="text-sm font-medium text-gray-700">Remarks</label>
                    </div>
                    <textarea
                        v-model="form.remarks"
                        placeholder="Add a remark"
                        rows="4"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    ></textarea>
                </div>

                <!-- Relevant/Irrelevant -->
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <label class="text-sm font-medium text-gray-700">Relevant</label>
                    </div>
                    <div class="flex gap-2">
                        <button
                            type="button"
                            @click="form.relevant = true"
                            :class="[
                                'px-4 py-2 rounded-md text-sm font-medium transition-colors',
                                form.relevant === true
                                    ? 'bg-green-500 text-white'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                            ]"
                        >
                            Relevant
                        </button>
                        <button
                            type="button"
                            @click="form.relevant = false"
                            :class="[
                                'px-4 py-2 rounded-md text-sm font-medium transition-colors',
                                form.relevant === false
                                    ? 'bg-red-500 text-white'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                            ]"
                        >
                            Irrelevant
                        </button>
                    </div>
                    
                    <!-- Irrelevant Reason Dropdown (shown when irrelevant is selected) -->
                    <div v-if="form.relevant === false" class="mt-3">
                        <select
                            v-model="form.irrelevant_reason_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 appearance-none bg-white"
                        >
                            <option value="">Select a reason</option>
                            <option v-for="reason in irrelevantReasons" :key="reason.id" :value="reason.id">
                                {{ reason.label }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 pt-4 border-t border-gray-200">
                    <button
                        type="button"
                        @click="close"
                        class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-md font-medium hover:bg-gray-200 transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="isSubmitting"
                        class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-md font-medium hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ isSubmitting ? 'Adding...' : 'Add' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const page = usePage();

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['close', 'created']);

const isSubmitting = ref(false);
const countries = ref([]);
const states = ref([]);
const cities = ref([]);
const allCities = ref([]); // All cities with state/country info
const quickCityId = ref(''); // For quick city search
const industries = ref([]);
const marketers = ref([]);
const sources = ref([]);
const languages = ref([]);
const irrelevantReasons = ref([]);

const form = reactive({
    pic_name: '',
    Shop_Name: '',
    phone_number: '',
    country_id: '',
    state_id: '',
    city_id: '',
    industry_id: '',
    marketer_id: '',
    source_id: '',
    language_id: '',
    relevant: true,
    irrelevant_reason_id: '',
    remarks: '',
    Manual_Source: '',
    Manual_Language: '',
});

// Load initial data
onMounted(async () => {
    await Promise.all([
        loadCountries(),
        loadAllCities(),
        loadIndustries(),
        loadMarketers(),
        loadSources(),
        loadLanguages(),
        loadIrrelevantReasons(),
    ]);
    // Load states and cities after cities are loaded (so we can extract unique states and show all cities)
    loadStates();
    loadCities();
});

const loadCountries = async () => {
    try {
        const response = await axios.get('/api/v1/countries');
        countries.value = response.data.data;
    } catch (error) {
        console.error('Error loading countries:', error);
        console.error('Response:', error.response);
    }
};

const loadAllCities = async () => {
    try {
        const response = await axios.get('/api/v1/cities');
        allCities.value = response.data.data;
    } catch (error) {
        console.error('Error loading cities:', error);
        console.error('Response:', error.response);
    }
};

const loadStates = async () => {
    if (form.country_id) {
        // Load states filtered by country
        try {
            const response = await axios.get(`/api/v1/countries/${form.country_id}/states`);
            states.value = response.data.data;
        } catch (error) {
            console.error('Error loading states:', error);
        }
    } else {
        // If no country selected, show all states (extract unique states from allCities)
        const uniqueStates = new Map();
        allCities.value.forEach(city => {
            if (city.state_id && city.state_name && !uniqueStates.has(city.state_id)) {
                uniqueStates.set(city.state_id, {
                    id: city.state_id,
                    name: city.state_name
                });
            }
        });
        states.value = Array.from(uniqueStates.values()).sort((a, b) => a.name.localeCompare(b.name));
    }
};

const loadCities = async () => {
    if (form.state_id) {
        // Load cities by state
        try {
            const response = await axios.get(`/api/v1/states/${form.state_id}/cities`);
            cities.value = response.data.data;
        } catch (error) {
            console.error('Error loading cities:', error);
        }
    } else if (form.country_id) {
        // If only country is selected, show all cities from that country
        // We'll filter from allCities
        cities.value = allCities.value
            .filter(city => city.country_id == form.country_id)
            .map(city => ({ id: city.id, name: city.name }));
    } else {
        // Show all cities if no filters
        cities.value = allCities.value.map(city => ({ id: city.id, name: city.name }));
    }
};

const onCountryChange = () => {
    // When country changes, load states and reset state/city if they don't match
    if (form.country_id) {
        loadStates();
        // Clear state/city if they don't belong to the new country
        if (form.state_id) {
            const state = states.value.find(s => s.id == form.state_id);
            if (!state) {
                form.state_id = '';
                form.city_id = '';
                quickCityId.value = '';
            }
        }
        if (form.city_id) {
            const city = allCities.value.find(c => c.id == form.city_id);
            if (city && city.country_id != form.country_id) {
                form.city_id = '';
                quickCityId.value = '';
            }
        }
        loadCities();
    } else {
        // If country is cleared, reload all states and cities
        loadStates();
        loadCities();
    }
};

const onStateChange = async () => {
    // When state changes, auto-fill country if not set, then load cities
    if (form.state_id) {
        // Auto-fill country from state if not already set
        if (!form.country_id) {
            // Try to find country from allCities first
            const cityWithState = allCities.value.find(c => c.state_id == form.state_id);
            if (cityWithState && cityWithState.country_id) {
                form.country_id = cityWithState.country_id;
                // Reload states for the country to ensure consistency
                await loadStates();
            } else {
                // Fetch state details from API
                try {
                    const response = await axios.get(`/api/v1/states/${form.state_id}`);
                    const stateData = response.data.data;
                    if (stateData.country_id) {
                        form.country_id = stateData.country_id;
                        await loadStates();
                    }
                } catch (error) {
                    console.error('Error loading state details:', error);
                }
            }
        }
        loadCities();
    } else {
        form.city_id = '';
        quickCityId.value = '';
        // Reload cities to show all when state is cleared
        loadCities();
    }
};

const onCityChange = async () => {
    // When city changes, auto-fill state and country if not set
    if (!form.city_id) {
        quickCityId.value = '';
        return;
    }

    // Find the selected city from cities list (filtered by state/country)
    let selectedCity = cities.value.find(city => city.id == form.city_id);
    
    if (!selectedCity) {
        // If not found in filtered list, search in allCities
        selectedCity = allCities.value.find(city => city.id == form.city_id);
    }
    
    if (selectedCity && 'state_id' in selectedCity) {
        // Auto-fill state and country if not already set
        if (!form.state_id && selectedCity.state_id) {
            form.state_id = selectedCity.state_id;
            await loadStates(); // Reload states to ensure they're available
        }
        if (!form.country_id && selectedCity.country_id) {
            form.country_id = selectedCity.country_id;
            await loadStates(); // Reload states for the country
        }
    } else {
        // If not found in cached list, fetch from API
        try {
            const response = await axios.get(`/api/v1/cities/${form.city_id}`);
            const cityData = response.data.data;
            if (!form.state_id && cityData.state_id) {
                form.state_id = cityData.state_id;
                await loadStates();
            }
            if (!form.country_id && cityData.country_id) {
                form.country_id = cityData.country_id;
                await loadStates();
            }
        } catch (error) {
            console.error('Error loading city details:', error);
        }
    }
    
    quickCityId.value = '';
};

const onQuickCityChange = async () => {
    // When quick city search is used, auto-fill everything
    if (!quickCityId.value) {
        return;
    }

    const selectedCity = allCities.value.find(city => city.id == quickCityId.value);
    
    if (selectedCity) {
        form.city_id = selectedCity.id;
        if (selectedCity.state_id) {
            form.state_id = selectedCity.state_id;
            await loadStates();
        }
        if (selectedCity.country_id) {
            form.country_id = selectedCity.country_id;
            await loadStates();
        }
        // Load cities for the selected state
        await loadCities();
    } else {
        // Fetch from API if not in cache
        try {
            const response = await axios.get(`/api/v1/cities/${quickCityId.value}`);
            const cityData = response.data.data;
            form.city_id = cityData.id;
            if (cityData.state_id) {
                form.state_id = cityData.state_id;
                await loadStates();
            }
            if (cityData.country_id) {
                form.country_id = cityData.country_id;
                await loadStates();
            }
            await loadCities();
        } catch (error) {
            console.error('Error loading city details:', error);
        }
    }
};

const loadIndustries = async () => {
    try {
        const response = await axios.get('/api/v1/lookups/' + encodeURIComponent('Lead Industry'));
        industries.value = response.data.data;
    } catch (error) {
        console.error('Error loading industries:', error);
        console.error('Response:', error.response);
    }
};

const loadMarketers = async () => {
    try {
        const response = await axios.get('/api/v1/marketers');
        marketers.value = response.data.data;
    } catch (error) {
        console.error('Error loading marketers:', error);
        console.error('Response:', error.response);
    }
};

const loadSources = async () => {
    try {
        const response = await axios.get('/api/v1/lookups/' + encodeURIComponent('Lead Source'));
        sources.value = response.data.data;
    } catch (error) {
        console.error('Error loading sources:', error);
        console.error('Response:', error.response);
    }
};

const loadLanguages = async () => {
    try {
        const response = await axios.get('/api/v1/lookups/' + encodeURIComponent('Lead Language'));
        languages.value = response.data.data;
    } catch (error) {
        console.error('Error loading languages:', error);
        console.error('Response:', error.response);
    }
};

const loadIrrelevantReasons = async () => {
    try {
        const response = await axios.get('/api/v1/lookups/' + encodeURIComponent('Lead Irrelevant Reason'));
        irrelevantReasons.value = response.data.data;
    } catch (error) {
        console.error('Error loading irrelevant reasons:', error);
        console.error('Response:', error.response);
    }
};

const close = () => {
    emit('close');
    resetForm();
};

const resetForm = () => {
    Object.assign(form, {
        pic_name: '',
        Shop_Name: '',
        phone_number: '',
        country_id: '',
        state_id: '',
        city_id: '',
        industry_id: '',
        marketer_id: '',
        source_id: '',
        language_id: '',
        relevant: true,
        irrelevant_reason_id: '',
        remarks: '',
        Manual_Source: '',
        Manual_Language: '',
    });
    quickCityId.value = '';
    states.value = [];
    cities.value = [];
};

const submitForm = async () => {
    if (!form.Shop_Name) {
        alert('Company name is required');
        return;
    }

    isSubmitting.value = true;
    try {
        // Get industry name if selected
        let manualIndustry = null;
        if (form.industry_id) {
            const industry = industries.value.find(i => i.id === form.industry_id);
            if (industry) {
                manualIndustry = industry.label;
            }
        }

        const payload = {
            Shop_Name: form.Shop_Name,
            pic_name: form.pic_name,
            phone_number: form.phone_number,
            country_id: form.country_id || null,
            state_id: form.state_id || null,
            city_id: form.city_id || null,
            Manual_Industry: manualIndustry,
            marketer_id: form.marketer_id || null,
            Manual_Source: form.Manual_Source || null,
            Manual_Language: form.Manual_Language || null,
            relevant: form.relevant,
            irrelevant_reason_id: form.irrelevant_reason_id || null,
            remarks: form.remarks || null,
        };

        const response = await axios.post('/api/v1/leads', payload);
        
        emit('created', response.data.data);
        close();
    } catch (error) {
        console.error('Error creating lead:', error);
        alert(error.response?.data?.message || 'Failed to create lead. Please try again.');
    } finally {
        isSubmitting.value = false;
    }
};
</script>

