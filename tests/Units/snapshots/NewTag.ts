import type BaseModel from './BaseModel'
import type TestClassTagPivot from './TestClassTagPivot'
import type NewTestClass from './NewTestClass'

// <non-auto-generated-import-declarations>

// </non-auto-generated-import-declarations>

export default interface NewTag extends BaseModel {
    assignment?: TestClassTagPivot;
    created_at?: string;
    id?: number;
    name?: string;
    test_classes?: NewTestClass[];
    updated_at?: string;

    // <non-auto-generated-class-declarations>

    // </non-auto-generated-class-declarations>
}

// <non-auto-generated-code>

// </non-auto-generated-code>

export const NewTagConstants = {
    phpQualifiedClassName: 'TsWinkTests\\Units\\Input\\Tag',
}
