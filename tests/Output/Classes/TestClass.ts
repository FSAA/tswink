import EventType from "./EventType"
import Introduction from "./Introduction"
// <non-auto-generated-import-declarations>

import TestImport from "./TestImport"
// </non-auto-generated-import-declarations>

export default class TestClass {

    public static readonly TEST_CONST: number = 45;
    public birth_amount?: number;
    public breeding_event_id?: number;
    public breeding_projection_chart_week?: number;
    public created_at?: Date;
    public cycle?: number;
    public deleted_at?: Date;
    public end_date?: Date;
    public event_type?: EventType;
    public event_type_id?: number;
    public female_cycle?: number;
    public female_inventory?: number;
    public female_inventory_order?: number;
    public female_type_id?: number;
    public id?: number;
    public introductions?: Introduction[] | { [key: string]: Introduction };
    public inventory_change?: number;
    public parent_event_id?: number;
    public reproduction_sold_amount?: number;
    public simulation_breed_group_id?: number;
    public start_date?: Date;
    public testAccessor?: any;
    public updated_at?: Date;
    
    constructor(init?: Partial<TestClass>) {
        Object.assign(this, init);
        init.created_at = init?.created_at ? new Date(init.created_at) : undefined;
        init.deleted_at = init?.deleted_at ? new Date(init.deleted_at) : undefined;
        init.end_date = init?.end_date ? new Date(init.end_date) : undefined;
        init.event_type = init?.event_type ? new EventType(init.event_type) : undefined;
        init.introductions = init?.introductions ? Object.deserialize<Introduction>(init.introductions, Introduction) : undefined;
        init.start_date = init?.start_date ? new Date(init.start_date) : undefined;
        init.updated_at = init?.updated_at ? new Date(init.updated_at) : undefined;
    }
    
    // <non-auto-generated-class-declarations>
    public testAttribute: any;
    
    public testFunction(): any {
    
    
    
    }
    // </non-auto-generated-class-declarations>
}